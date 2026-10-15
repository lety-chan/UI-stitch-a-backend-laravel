<?php

namespace Tests\Feature;

use App\Models\AiChatMessage;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_analytics_restock_and_assistant_screens_require_authentication(): void
    {
        foreach (['inventory.index', 'analytics.index', 'restock.index', 'assistant.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_inventory_lists_prioritized_items_and_filters_products_by_search_and_warehouse(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $central = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $north = Warehouse::create(['name' => 'Bodega Norte', 'code' => 'NORTH']);
        $critical = $this->product($central, ['name' => 'Teclado RGB', 'sku' => 'TEC-001', 'current_stock' => 2, 'minimum_stock' => 5]);
        $this->product($north, ['name' => 'Monitor UltraWide', 'sku' => 'MON-001', 'current_stock' => 40, 'minimum_stock' => 8]);

        $this->actingAs($user)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertViewIs('inventory.index')
            ->assertSee('Teclado RGB')
            ->assertSee('Monitor UltraWide')
            ->assertSee('Stock crítico')
            ->assertSee('1</p>', false);

        $this->get(route('inventory.index', ['filter' => 'central']))
            ->assertOk()
            ->assertSee('Teclado RGB')
            ->assertDontSee('Monitor UltraWide');

        $this->get(route('inventory.index', ['filter' => 'critical']))
            ->assertOk()
            ->assertSee('Teclado RGB')
            ->assertDontSee('Monitor UltraWide');

        $this->get(route('inventory.index', ['search' => 'MON-001']))
            ->assertOk()
            ->assertSee('Monitor UltraWide')
            ->assertDontSee('Teclado RGB');

        $this->assertSame('critical', $critical->stock_level);
    }

    public function test_product_creation_records_initial_stock_as_a_confirmed_movement(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $supplier = Supplier::create(['name' => 'Proveedor Uno', 'lead_time_days' => 5]);

        $this->actingAs($user)->post(route('inventory.store'), [
            'sku' => 'TEC-002',
            'name' => 'Teclado Mecánico RGB',
            'description' => 'Teclado de prueba',
            'price' => '89.00',
            'cost' => '54.00',
            'initial_stock' => 12,
            'minimum_stock' => 4,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'location_details' => 'Pasillo 4 · Estante B',
        ])->assertRedirect(route('inventory.index'));

        $product = Product::query()->where('sku', 'TEC-002')->firstOrFail();

        $this->assertSame(12, $product->current_stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 12,
            'previous_stock' => 0,
            'new_stock' => 12,
            'status' => 'confirmed',
        ]);
    }

    public function test_user_can_create_warehouses_and_suppliers_for_product_catalog(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('inventory.warehouses.store'), [
            'name' => 'Bodega Norte',
            'code' => 'NORTH-01',
            'address' => 'Zona industrial',
        ])->assertRedirect(route('inventory.create'));

        $this->post(route('inventory.suppliers.store'), [
            'name' => 'Proveedor Central',
            'lead_time_days' => 7,
            'contact_email' => 'ventas@example.test',
        ])->assertRedirect(route('inventory.create'));

        $this->assertDatabaseHas('warehouses', ['code' => 'NORTH-01']);
        $this->assertDatabaseHas('suppliers', ['contact_email' => 'ventas@example.test']);
    }

    public function test_analytics_uses_confirmed_outgoing_movements_for_real_sales(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $product = $this->product($warehouse, [
            'name' => 'Sensor Térmico',
            'sku' => 'SEN-001',
            'price' => 10,
            'cost' => 5,
            'current_stock' => 20,
            'minimum_stock' => 2,
        ]);

        $this->movement($product, $user, [
            'type' => 'out',
            'quantity' => 4,
            'previous_stock' => 24,
            'new_stock' => 20,
            'status' => 'confirmed',
        ]);
        $this->movement($product, $user, [
            'type' => 'out',
            'quantity' => 9,
            'previous_stock' => 29,
            'new_stock' => 20,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('analytics.index', ['period' => 'month']))
            ->assertOk()
            ->assertViewIs('analytics.index')
            ->assertSee('$40')
            ->assertSee('Sensor Térmico')
            ->assertSee('4');
    }

    public function test_restock_orders_can_be_created_approved_and_received_without_duplicate_orders(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $supplier = Supplier::create(['name' => 'Proveedor Uno', 'lead_time_days' => 6]);
        $product = $this->product($warehouse, [
            'name' => 'Teclado RGB',
            'sku' => 'TEC-001',
            'cost' => 20,
            'current_stock' => 1,
            'minimum_stock' => 5,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($user)
            ->get(route('restock.index'))
            ->assertOk()
            ->assertViewIs('restock.index')
            ->assertSee('Teclado RGB')
            ->assertSee('6 días');

        $this->post(route('restock.orders.store', $product))
            ->assertRedirect(route('restock.index'));

        $order = PurchaseOrder::query()->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
        ]);
        $this->get(route('restock.index'))
            ->assertOk()
            ->assertSee('Orden ya creada')
            ->assertSee('0.00');

        $this->post(route('restock.orders.store', $product))->assertStatus(422);

        $this->post(route('restock.orders.approve', $order))
            ->assertRedirect(route('restock.index'));
        $this->assertSame('approved', $order->fresh()->status);

        $this->post(route('restock.orders.receive', $order))
            ->assertRedirect(route('restock.index'));

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 4,
            'status' => 'confirmed',
        ]);
    }

    public function test_bulk_approval_creates_one_approved_order_for_each_critical_product(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $supplier = Supplier::create(['name' => 'Proveedor Uno', 'lead_time_days' => 5]);
        $product = $this->product($warehouse, [
            'name' => 'Cámara Térmica',
            'sku' => 'CAM-001',
            'current_stock' => 0,
            'minimum_stock' => 3,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($user)
            ->post(route('restock.approve-all'))
            ->assertRedirect(route('restock.index'));

        $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $supplier->id, 'status' => 'approved']);
        $this->assertDatabaseHas('purchase_order_items', ['product_id' => $product->id]);

        $this->post(route('restock.approve-all'))->assertRedirect(route('restock.index'));
        $this->assertDatabaseCount('purchase_orders', 1);
    }

    public function test_a_supplier_can_be_assigned_to_a_product_before_ordering(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $supplier = Supplier::create(['name' => 'Proveedor Central', 'lead_time_days' => 9]);
        $product = $this->product($warehouse, [
            'name' => 'Sensor Térmico',
            'sku' => 'SEN-022',
            'current_stock' => 0,
            'minimum_stock' => 3,
            'supplier_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('restock.index'))
            ->assertOk()
            ->assertSee('Proveedor Central')
            ->assertSee('Asignar');

        $this->post(route('restock.products.supplier', $product), [
            'supplier_id' => $supplier->id,
        ])->assertRedirect(route('restock.index'));

        $this->assertSame($supplier->id, $product->fresh()->supplier_id);
        $this->post(route('restock.orders.store', $product))->assertRedirect(route('restock.index'));
        $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $supplier->id, 'status' => 'pending']);
    }

    public function test_auto_approval_toggle_applies_to_new_restock_orders(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $supplier = Supplier::create(['name' => 'Proveedor Uno', 'lead_time_days' => 5]);
        $product = $this->product($warehouse, [
            'name' => 'Monitor',
            'sku' => 'MON-002',
            'current_stock' => 1,
            'minimum_stock' => 5,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($user)
            ->post(route('restock.toggle-auto-approve'))
            ->assertRedirect(route('restock.index'));

        $this->post(route('restock.orders.store', $product))
            ->assertRedirect(route('restock.index'));

        $this->assertDatabaseHas('purchase_orders', ['supplier_id' => $supplier->id, 'status' => 'approved']);
    }

    public function test_assistant_previews_and_confirms_a_stock_receipt(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega B', 'code' => 'B']);
        $product = $this->product($warehouse, [
            'name' => 'Teclado RGB',
            'sku' => 'TEC-099',
            'current_stock' => 12,
            'minimum_stock' => 3,
        ]);

        $this->actingAs($user)
            ->post(route('assistant.send'), [
                'message' => 'Recibimos 30 unidades del Teclado RGB en Bodega B. Actualiza el stock por favor.',
            ])
            ->assertRedirect(route('assistant.index'));

        $movement = InventoryMovement::query()->firstOrFail();
        $this->assertSame('pending', $movement->status);
        $this->assertSame(12, $product->fresh()->current_stock);

        $this->get(route('assistant.index'))
            ->assertOk()
            ->assertViewIs('assistant.index')
            ->assertSee('Confirmación de movimiento')
            ->assertSee('Cancelar')
            ->assertSee('Confirmar');

        $this->post(route('assistant.movements.confirm', $movement))
            ->assertRedirect(route('assistant.index'));

        $this->assertSame('confirmed', $movement->fresh()->status);
        $this->assertSame(42, $product->fresh()->current_stock);
        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $user->id,
            'sender' => 'ai',
            'message' => 'Movimiento confirmado. El stock de Teclado RGB ahora es 42 unidades.',
        ]);
    }

    public function test_assistant_cancels_a_proposed_outgoing_movement_without_changing_stock(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $product = $this->product($warehouse, [
            'name' => 'Sensor Térmico',
            'sku' => 'SEN-001',
            'current_stock' => 10,
            'minimum_stock' => 2,
        ]);

        $this->actingAs($user)->post(route('assistant.send'), [
            'message' => 'Despachamos 3 unidades del Sensor Térmico en Bodega Central.',
        ])->assertRedirect(route('assistant.index'));

        $movement = InventoryMovement::query()->firstOrFail();
        $this->assertSame('pending', $movement->status);

        $this->post(route('assistant.movements.cancel', $movement))
            ->assertRedirect(route('assistant.index'));

        $this->assertSame('cancelled', $movement->fresh()->status);
        $this->assertSame(10, $product->fresh()->current_stock);
    }

    public function test_assistant_rejects_a_stock_outflow_that_exceeds_available_inventory(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $product = $this->product($warehouse, [
            'name' => 'Sensor Térmico',
            'sku' => 'SEN-001',
            'current_stock' => 2,
            'minimum_stock' => 1,
        ]);

        $this->actingAs($user)->post(route('assistant.send'), [
            'message' => 'Despachamos 5 unidades del Sensor Térmico.',
        ])->assertRedirect(route('assistant.index'));

        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertSame(2, $product->fresh()->current_stock);
        $this->assertSame(1, AiChatMessage::query()->where('sender', 'ai')->count());
    }

    public function test_assistant_forecasts_coverage_from_confirmed_sales_without_claiming_an_external_ai_model(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $product = $this->product($warehouse, [
            'name' => 'Sensor Térmico',
            'sku' => 'SEN-001',
            'current_stock' => 20,
            'minimum_stock' => 2,
        ]);
        $this->movement($product, $user, [
            'type' => 'out',
            'quantity' => 10,
            'previous_stock' => 30,
            'new_stock' => 20,
            'status' => 'confirmed',
        ]);

        $this->actingAs($user)->post(route('assistant.send'), [
            'message' => 'Proyecta la cobertura del Sensor Térmico',
        ])->assertRedirect(route('assistant.index'));

        $this->assertSame(1, AiChatMessage::query()->where('sender', 'ai')->count());
        $this->assertDatabaseHas('ai_chat_messages', [
            'user_id' => $user->id,
            'sender' => 'ai',
            'message' => 'Proyección de Sensor Térmico: 20 unidades cubren aproximadamente 60 días, según el promedio de salidas confirmadas de los últimos 30 días. Es una estimación descriptiva, no un modelo predictivo de IA.',
        ]);
        $this->assertSame(20, $product->fresh()->current_stock);
    }

    public function test_assistant_cannot_confirm_or_cancel_a_movement_owned_by_another_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $product = $this->product($warehouse, ['name' => 'Sensor Térmico', 'sku' => 'SEN-001']);
        $movement = $this->movement($product, $owner, [
            'type' => 'in',
            'quantity' => 2,
            'previous_stock' => 8,
            'new_stock' => 10,
            'status' => 'pending',
        ]);

        $this->actingAs($otherUser)
            ->post(route('assistant.movements.confirm', $movement))
            ->assertNotFound();

        $this->post(route('assistant.movements.cancel', $movement))
            ->assertNotFound();

        $this->assertSame('pending', $movement->fresh()->status);
        $this->assertSame(8, $product->fresh()->current_stock);
    }

    public function test_confirming_a_stale_outflow_does_not_overdraw_inventory(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::create(['name' => 'Bodega Central', 'code' => 'CENTRAL']);
        $product = $this->product($warehouse, [
            'name' => 'Sensor Térmico',
            'sku' => 'SEN-001',
            'current_stock' => 10,
            'minimum_stock' => 2,
        ]);

        $this->actingAs($user)->post(route('assistant.send'), [
            'message' => 'Despachamos 8 unidades del Sensor Térmico.',
        ])->assertRedirect(route('assistant.index'));

        $movement = InventoryMovement::query()->firstOrFail();
        $product->update(['current_stock' => 3]);

        $this->from(route('assistant.index'))
            ->post(route('assistant.movements.confirm', $movement))
            ->assertRedirect(route('assistant.index'))
            ->assertSessionHasErrors('movement');

        $this->assertSame(3, $product->fresh()->current_stock);
        $this->assertSame('pending', $movement->fresh()->status);
    }

    public function test_all_four_module_views_render_for_an_authenticated_user(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ([
            'inventory.index',
            'analytics.index',
            'restock.index',
            'assistant.index',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    private function product(Warehouse $warehouse, array $attributes = []): Product
    {
        return Product::create([
            'sku' => 'SKU-'.fake()->unique()->numerify('####'),
            'name' => 'Producto de prueba',
            'price' => 20,
            'cost' => 10,
            'current_stock' => 8,
            'minimum_stock' => 2,
            'status' => 'active',
            'warehouse_id' => $warehouse->id,
            ...$attributes,
        ]);
    }

    private function movement(Product $product, User $user, array $attributes): InventoryMovement
    {
        return InventoryMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'warehouse_id' => $product->warehouse_id,
            'type' => 'out',
            'quantity' => 1,
            'previous_stock' => 9,
            'new_stock' => 8,
            'notes' => 'Fixture de analítica.',
            'status' => 'confirmed',
            ...$attributes,
        ]);
    }
}
