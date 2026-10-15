<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RestockController extends Controller
{
    public function index(): View
    {
        $salesByProduct = InventoryMovement::query()
            ->confirmed()
            ->outgoing()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('product_id, SUM(quantity) AS units_sold')
            ->groupBy('product_id')
            ->pluck('units_sold', 'product_id');

        $openOrders = PurchaseOrder::query()
            ->with(['supplier', 'items.product'])
            ->whereIn('status', ['suggested', 'pending', 'approved'])
            ->latest()
            ->limit(20)
            ->get();
        $productsWithOpenOrders = PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', fn ($query) => $query->whereIn('status', ['suggested', 'pending', 'approved']))
            ->pluck('product_id')
            ->unique()
            ->flip();

        $recommendations = Product::query()
            ->active()
            ->with(['supplier', 'warehouse'])
            ->orderBy('current_stock')
            ->get()
            ->map(fn (Product $product): array => $this->recommendation(
                $product,
                (int) ($salesByProduct[$product->id] ?? 0),
            ))
            ->filter(fn (array $recommendation): bool => $recommendation['at_risk'])
            ->map(function (array $recommendation) use ($productsWithOpenOrders): array {
                $recommendation['has_open_order'] = $productsWithOpenOrders->has($recommendation['product']->id);

                return $recommendation;
            })
            ->values();

        $newRecommendations = $recommendations->where('has_open_order', false);
        $bulkInvestment = $newRecommendations
            ->filter(fn (array $recommendation): bool => $recommendation['product']->current_stock <= $recommendation['product']->minimum_stock)
            ->sum('estimated_cost');

        return view('restock.index', [
            'recommendations' => $recommendations,
            'investment' => $newRecommendations->sum('estimated_cost'),
            'bulkInvestment' => $bulkInvestment,
            'pendingCount' => PurchaseOrder::query()->whereIn('status', ['suggested', 'pending'])->count(),
            'autoApprove' => (bool) session('restock.auto_approve', false),
            'openOrders' => $openOrders,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function assignSupplier(Product $product, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
        ]);

        abort_unless($product->status === 'active', 404);
        abort_if($this->hasOpenOrder($product), 422, 'No se puede cambiar el proveedor mientras exista una orden abierta.');
        $product->update(['supplier_id' => $validated['supplier_id']]);

        return redirect()->route('restock.index')->with('status', 'Proveedor asignado. Ya puedes generar la orden sugerida.');
    }

    public function toggleAutoApprove(Request $request): RedirectResponse
    {
        $request->session()->put('restock.auto_approve', ! (bool) $request->session()->get('restock.auto_approve', false));

        return redirect()->route('restock.index');
    }

    public function generateOrder(Product $product, Request $request): RedirectResponse
    {
        abort_unless($product->status === 'active', 404);
        abort_if($product->supplier_id === null, 422, 'Asigna un proveedor antes de generar una orden de compra.');

        $order = DB::transaction(function () use ($product, $request): ?PurchaseOrder {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $this->ensureNoOpenOrder($lockedProduct);
            $recommendation = $this->recommendation($lockedProduct, $this->recentUnitsSold($lockedProduct));

            return $this->createOrder($lockedProduct, $recommendation, (bool) $request->session()->get('restock.auto_approve', false));
        });

        return redirect()->route('restock.index')
            ->with('status', 'Orden #'.$order->id.' generada: '.$order->items()->first()->quantity.' unidades solicitadas.');
    }

    public function approveAll(): RedirectResponse
    {
        $created = DB::transaction(function (): int {
            $salesByProduct = InventoryMovement::query()
                ->confirmed()
                ->outgoing()
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('product_id, SUM(quantity) AS units_sold')
                ->groupBy('product_id')
                ->pluck('units_sold', 'product_id');

            $products = Product::query()
                ->active()
                ->whereNotNull('supplier_id')
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->lockForUpdate()
                ->get();

            $created = 0;
            foreach ($products as $product) {
                if ($this->hasOpenOrder($product)) {
                    continue;
                }

                $recommendation = $this->recommendation(
                    $product,
                    (int) ($salesByProduct[$product->id] ?? 0),
                );
                $this->createOrder($product, $recommendation, true);
                $created++;
            }

            return $created;
        });

        return redirect()->route('restock.index')->with(
            'status',
            $created > 0
                ? "{$created} órdenes sugeridas fueron aprobadas."
                : 'No hay recomendaciones críticas nuevas para aprobar.',
        );
    }

    public function approveOrder(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless(in_array($purchaseOrder->status, ['suggested', 'pending'], true), 422);

        $purchaseOrder->update(['status' => 'approved']);

        return redirect()->route('restock.index')->with('status', "La orden #{$purchaseOrder->id} fue aprobada.");
    }

    public function receiveOrder(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse
    {
        abort_unless($purchaseOrder->status === 'approved', 422);

        DB::transaction(function () use ($purchaseOrder, $request): void {
            $lockedOrder = PurchaseOrder::query()
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);
            abort_unless($lockedOrder->status === 'approved', 422);

            foreach ($lockedOrder->items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $previousStock = $product->current_stock;
                $newStock = $previousStock + $item->quantity;

                $product->update(['current_stock' => $newStock]);
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'warehouse_id' => $product->warehouse_id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'notes' => "Recepción de orden #{$lockedOrder->id}.",
                    'status' => 'confirmed',
                ]);
            }

            $lockedOrder->update(['status' => 'completed']);
        });

        return redirect()->route('restock.index')->with('status', "La orden #{$purchaseOrder->id} fue recibida y el stock actualizado.");
    }

    /**
     * @return array<string, mixed>
     */
    private function recommendation(Product $product, int $unitsSold): array
    {
        $dailyDemand = $unitsSold / 30;
        $leadTime = $product->supplier?->lead_time_days ?? 7;
        $daysRemaining = $dailyDemand > 0
            ? (int) floor($product->current_stock / $dailyDemand)
            : ($product->current_stock <= $product->minimum_stock ? 0 : null);
        $atRisk = $product->current_stock <= $product->minimum_stock
            || ($daysRemaining !== null && $daysRemaining <= $leadTime + 2);
        $quantity = max(
            1,
            (int) ceil($dailyDemand * ($leadTime + 14) + $product->minimum_stock - $product->current_stock),
        );

        return [
            'product' => $product,
            'units_sold' => $unitsSold,
            'daily_demand' => $dailyDemand,
            'lead_time' => $leadTime,
            'days_remaining' => $daysRemaining,
            'at_risk' => $atRisk,
            'quantity' => $quantity,
            'break_date' => $daysRemaining === null ? null : CarbonImmutable::now()->addDays($daysRemaining)->startOfDay(),
            'estimated_cost' => $quantity * (float) $product->cost,
        ];
    }

    private function recentUnitsSold(Product $product): int
    {
        return (int) $product->inventoryMovements()
            ->confirmed()
            ->outgoing()
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('quantity');
    }

    private function ensureNoOpenOrder(Product $product): void
    {
        abort_if($this->hasOpenOrder($product), 422, 'Ya existe una orden abierta para este producto.');
    }

    private function hasOpenOrder(Product $product): bool
    {
        return PurchaseOrder::query()
            ->whereIn('status', ['suggested', 'pending', 'approved'])
            ->whereHas('items', fn ($items) => $items->where('product_id', $product->id))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $recommendation
     */
    private function createOrder(Product $product, array $recommendation, bool $approved): PurchaseOrder
    {
        $quantity = $recommendation['quantity'];
        $unitCost = (float) $product->cost;
        $subtotal = round($quantity * $unitCost, 2);
        $order = PurchaseOrder::create([
            'supplier_id' => $product->supplier_id,
            'total_cost' => $subtotal,
            'status' => $approved ? 'approved' : 'pending',
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'subtotal' => $subtotal,
        ]);

        return $order;
    }
}
