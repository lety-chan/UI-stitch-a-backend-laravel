<?php

namespace App\Http\Controllers;

use App\Models\AiChatMessage;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function index(Request $request): View
    {
        $messages = $request->user()->aiChatMessages()->latest('created_at')->latest('id')->limit(50)->get()->reverse()->values();
        $movementIds = $messages
            ->map(fn (AiChatMessage $message): ?int => $message->metadata_json['movement_id'] ?? null)
            ->filter()
            ->unique();
        $movementCards = InventoryMovement::query()
            ->with(['product.warehouse', 'warehouse', 'user'])
            ->whereIn('id', $movementIds)
            ->get()
            ->keyBy('id');

        return view('assistant.index', compact('messages', 'movementCards'));
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $text = trim($validated['message']);
        $normalized = Str::lower(Str::ascii($text));
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use ($user, $text, $normalized): void {
            $user->aiChatMessages()->create([
                'sender' => 'user',
                'message' => $text,
                'created_at' => now(),
            ]);

            $product = $this->findMentionedProduct($text);

            if (preg_match('/\b(predic|proyecc|cobertura|demanda)\w*/', $normalized) === 1 && $product !== null) {
                $this->replyWithDemandAnalysis($user, $product);

                return;
            }

            if (preg_match('/\b(promoc|liquidac)\w*/', $normalized) === 1 && $product !== null) {
                $this->reply($user, "No hay reglas comerciales ni un proveedor de IA conectado para fijar descuentos de {$product->name}. Puedo mostrar el capital inmovilizado de este producto, pero no cambiaré su precio sin una configuración comercial.");

                return;
            }

            if (preg_match('/\b(audit|revisa|revisar)\w*/', $normalized) === 1 && $product !== null) {
                $recentIn = $product->inventoryMovements()
                    ->confirmed()
                    ->where('type', 'in')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->sum('quantity');
                $recentOut = $product->inventoryMovements()
                    ->confirmed()
                    ->outgoing()
                    ->where('created_at', '>=', now()->subDays(30))
                    ->sum('quantity');

                $this->reply($user, "Auditoría de {$product->name} ({$product->sku}): {$product->current_stock} unidades disponibles, mínimo {$product->minimum_stock}; entradas confirmadas en 30 días: {$recentIn}, salidas: {$recentOut}. No se detectaron ni aplicaron ajustes automáticos.");

                return;
            }

            $quantity = $this->mentionedQuantity($normalized);
            $type = preg_match('/\b(recib|ingres|entrad|llegad|compr)\w*/', $normalized) === 1
                ? 'in'
                : (preg_match('/\b(despach|retir|salid|vend|consum|remisi)\w*/', $normalized) === 1 ? 'out' : null);

            if ($product === null || $quantity === null || $type === null) {
                $this->reply($user, 'Puedo preparar una entrada o salida para confirmar, estimar la cobertura o revisar una sugerencia comercial. Indica el nombre o SKU, la cantidad en unidades y si se recibió o se despachó.');

                return;
            }

            $warehouse = $this->findMentionedWarehouse($text) ?? $product->warehouse;

            if ($warehouse === null) {
                $this->reply($user, 'Encontré el producto, pero no tiene una bodega asignada. Asigna una bodega al producto antes de registrar el movimiento.');

                return;
            }

            if ($type === 'out' && $product->warehouse_id !== null && $product->warehouse_id !== $warehouse->id) {
                $this->reply($user, 'El producto está asignado a '.$product->warehouse->name.' y la salida indica otra bodega. Cambia su bodega asignada antes de registrar una salida; el inventario actual no lleva existencias separadas por bodega.');

                return;
            }

            if ($type === 'out' && $quantity > $product->current_stock) {
                $this->reply($user, "La salida solicitada supera el stock disponible de {$product->current_stock} unidades. No preparé ningún cambio.");

                return;
            }

            $previousStock = $product->current_stock;
            $newStock = $type === 'in'
                ? $previousStock + $quantity
                : $previousStock - $quantity;
            $movement = InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'warehouse_id' => $warehouse->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'notes' => 'Movimiento propuesto mediante el asistente.',
                'status' => 'pending',
            ]);

            $user->aiChatMessages()->create([
                'sender' => 'ai',
                'message' => 'Preparé el movimiento para revisión. El inventario no cambiará hasta que confirmes la operación.',
                'metadata_json' => [
                    'type' => 'movement_confirmation',
                    'movement_id' => $movement->id,
                ],
                'created_at' => now(),
            ]);
        });

        return redirect()->route('assistant.index');
    }

    public function confirmMovement(InventoryMovement $inventoryMovement, Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $result = DB::transaction(function () use ($inventoryMovement, $user): string {
            $movement = $user->inventoryMovements()
                ->whereKey($inventoryMovement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($movement->status !== 'pending') {
                return 'Este movimiento ya fue procesado y no admite cambios.';
            }

            $product = Product::query()->lockForUpdate()->findOrFail($movement->product_id);
            $previousStock = $product->current_stock;

            if ($movement->type === 'out' && $movement->quantity > $previousStock) {
                throw ValidationException::withMessages([
                    'movement' => 'El stock cambió mientras se revisaba el movimiento. Actualiza la página y prepara una nueva solicitud.',
                ]);
            }

            $newStock = $movement->type === 'in'
                ? $previousStock + $movement->quantity
                : ($movement->type === 'out' ? $previousStock - $movement->quantity : $movement->quantity);

            $product->update([
                'current_stock' => $newStock,
                ...($movement->type === 'in' ? ['warehouse_id' => $movement->warehouse_id] : []),
            ]);
            $movement->update([
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'status' => 'confirmed',
            ]);

            return "Movimiento confirmado. El stock de {$product->name} ahora es {$newStock} unidades.";
        });

        $user->aiChatMessages()->create([
            'sender' => 'ai',
            'message' => $result,
            'created_at' => now(),
        ]);

        return redirect()->route('assistant.index')->with('status', $result);
    }

    public function cancelMovement(InventoryMovement $inventoryMovement, Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use ($user, $inventoryMovement): void {
            $movement = $user->inventoryMovements()
                ->whereKey($inventoryMovement->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($movement->status === 'pending', 422, 'El movimiento ya no se puede cancelar.');
            $movement->update(['status' => 'cancelled']);

            $user->aiChatMessages()->create([
                'sender' => 'ai',
                'message' => 'Movimiento cancelado. Las existencias no se modificaron.',
                'created_at' => now(),
            ]);
        });

        return redirect()->route('assistant.index');
    }

    private function findMentionedProduct(string $message): ?Product
    {
        $driver = DB::getDriverName();
        $pattern = $driver === 'sqlite'
            ? "? LIKE '%' || products.name || '%'"
            : "? LIKE CONCAT('%', products.name, '%')";
        $skuPattern = $driver === 'sqlite'
            ? "? LIKE '%' || LOWER(products.sku) || '%'"
            : "? LIKE CONCAT('%', LOWER(products.sku), '%')";

        return Product::query()
            ->active()
            ->with('warehouse')
            ->where(function ($query) use ($message, $pattern, $skuPattern): void {
                $query->whereRaw($pattern, [$message])
                    ->orWhereRaw($skuPattern, [Str::lower($message)]);
            })
            ->orderByRaw('LENGTH(products.name) DESC')
            ->first();
    }

    private function findMentionedWarehouse(string $message): ?Warehouse
    {
        $normalized = Str::lower(Str::ascii($message));

        return Warehouse::query()
            ->get()
            ->first(fn (Warehouse $warehouse): bool => Str::contains(
                $normalized,
                Str::lower(Str::ascii($warehouse->name)),
            ) || Str::contains($normalized, Str::lower($warehouse->code)));
    }

    private function mentionedQuantity(string $message): ?int
    {
        if (preg_match('/\b(\d+)\s*(?:unidades?|uds?)\b/', $message, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1] > 0 ? (int) $matches[1] : null;
    }

    private function replyWithDemandAnalysis(User $user, Product $product): void
    {
        $unitsInLast30Days = (int) $product->inventoryMovements()
            ->confirmed()
            ->outgoing()
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('quantity');
        $dailyDemand = $unitsInLast30Days / 30;
        $coverageDays = $dailyDemand > 0
            ? (int) floor($product->current_stock / $dailyDemand)
            : null;
        $message = $coverageDays === null
            ? "{$product->name} no tiene salidas confirmadas en los últimos 30 días; todavía no hay datos suficientes para estimar su cobertura."
            : "Proyección de {$product->name}: {$product->current_stock} unidades cubren aproximadamente {$coverageDays} días, según el promedio de salidas confirmadas de los últimos 30 días. Es una estimación descriptiva, no un modelo predictivo de IA.";

        $user->aiChatMessages()->create([
            'sender' => 'ai',
            'message' => $message,
            'metadata_json' => [
                'type' => 'demand_analysis',
                'product_id' => $product->id,
                'units_sold_30_days' => $unitsInLast30Days,
                'coverage_days' => $coverageDays,
            ],
            'created_at' => now(),
        ]);
    }

    private function reply(User $user, string $message): void
    {
        $user->aiChatMessages()->create([
            'sender' => 'ai',
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}
