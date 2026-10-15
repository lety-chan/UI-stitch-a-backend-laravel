<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString();

        $products = Product::query()
            ->with(['warehouse', 'supplier'])
            ->search($request->string('search')->toString())
            ->when($filter === 'critical', fn ($query) => $query->whereColumn('current_stock', '<=', 'minimum_stock'))
            ->when($filter === 'central', fn ($query) => $query->whereHas('warehouse', fn ($warehouse) => $warehouse->where('name', 'like', '%central%')))
            ->when(str_starts_with($filter, 'warehouse:'), fn ($query) => $query->where('warehouse_id', (int) substr($filter, 10)))
            ->orderByRaw('CASE WHEN current_stock <= minimum_stock THEN 0 WHEN current_stock <= minimum_stock * 1.5 THEN 1 ELSE 2 END')
            ->orderBy('current_stock')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'products' => Product::active()->count(),
            'items' => (int) Product::active()->sum('current_stock'),
            'critical' => Product::active()->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
        ];

        return view('inventory.index', [
            'products' => $products,
            'metrics' => $metrics,
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'filter' => $filter,
            'search' => $request->string('search')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('inventory.create', [
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp|avif)$/i'],
            'initial_stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $initialStock = $validated['initial_stock'];
            $product = Product::create([
                ...$validated,
                'current_stock' => 0,
                'status' => 'active',
            ]);

            if ($initialStock > 0) {
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'warehouse_id' => $product->warehouse_id,
                    'type' => 'in',
                    'quantity' => $initialStock,
                    'previous_stock' => 0,
                    'new_stock' => $initialStock,
                    'notes' => 'Existencias iniciales del producto.',
                    'status' => 'confirmed',
                ]);

                $product->update(['current_stock' => $initialStock]);
            }
        });

        return redirect()->route('inventory.index')->with('status', 'Producto creado y existencias iniciales registradas.');
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:warehouses,code'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        Warehouse::create($validated);

        return redirect()->route('inventory.create')->with('status', 'Bodega agregada. Ya puedes asignarla a un producto.');
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lead_time_days' => ['required', 'integer', 'min:1', 'max:365'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        Supplier::create($validated);

        return redirect()->route('inventory.create')->with('status', 'Proveedor agregado. Ya puedes asignarlo a un producto.');
    }
}
