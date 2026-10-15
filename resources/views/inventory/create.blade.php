@extends('layouts.app')

@section('title', 'Agregar producto | Deepeer AI')

@section('content')
    <main class="mx-auto min-h-screen w-full max-w-[480px] px-4 pb-10 sm:px-6">
        <x-module-header title="Nuevo producto" />
        <x-flash-message />
        <section class="mt-6">
            <a href="{{ route('inventory.index') }}" class="text-sm font-semibold text-[#cfafff]">← Volver al inventario</a>
            <h1 class="mt-4 text-2xl font-bold">Agregar producto</h1>
            <p class="mt-1 text-sm text-[#aaa2b6]">Las existencias iniciales también crean un movimiento de inventario auditable.</p>
        </section>

        <form action="{{ route('inventory.store') }}" method="POST" class="mt-6 space-y-4 rounded-2xl border border-white/5 bg-[#1d1a2d] p-4">
            @csrf
            <x-form-input id="name" name="name" label="Nombre del producto" :value="old('name')" required />
            <x-form-input id="sku" name="sku" label="SKU" :value="old('sku')" required />
            <x-form-input id="description" name="description" label="Descripción" :value="old('description')" />
            <div class="grid grid-cols-2 gap-3">
                <x-form-input id="price" name="price" label="Precio de venta" type="number" min="0" step="0.01" :value="old('price', '0')" required />
                <x-form-input id="cost" name="cost" label="Costo unitario" type="number" min="0" step="0.01" :value="old('cost', '0')" required />
                <x-form-input id="initial_stock" name="initial_stock" label="Stock inicial" type="number" min="0" :value="old('initial_stock', '0')" required />
                <x-form-input id="minimum_stock" name="minimum_stock" label="Stock mínimo" type="number" min="0" :value="old('minimum_stock', '0')" required />
            </div>
            <x-form-input id="image" name="image" label="Imagen (nombre del archivo en public/images)" placeholder="teclado-rgb.webp" :value="old('image')" />
            <x-form-input id="location_details" name="location_details" label="Estante o ubicación" placeholder="Pasillo 4 · Estante B" :value="old('location_details')" />

            <div>
                <label for="warehouse_id" class="mb-2 block text-sm font-semibold text-[#d2ccdc]">Bodega</label>
                <select id="warehouse_id" name="warehouse_id" class="w-full rounded-2xl border border-white/5 bg-[#211d31] px-4 py-3.5 text-sm text-[#eee9fa] outline-none focus:border-[#9148ff]">
                    <option value="">Selecciona una bodega</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->name }} · {{ $warehouse->code }}</option>
                    @endforeach
                </select>
                @error('warehouse_id')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
                @if ($warehouses->isEmpty())
                    <p class="mt-1 text-xs text-amber-200">Crea bodegas primero para poder asignar ubicaciones.</p>
                @endif
            </div>
            <div>
                <label for="supplier_id" class="mb-2 block text-sm font-semibold text-[#d2ccdc]">Proveedor</label>
                <select id="supplier_id" name="supplier_id" class="w-full rounded-2xl border border-white/5 bg-[#211d31] px-4 py-3.5 text-sm text-[#eee9fa] outline-none focus:border-[#9148ff]">
                    <option value="">Sin proveedor asignado</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<p class="mt-1 text-sm text-rose-300">{{ $message }}</p>@enderror
            </div>
            <x-primary-button class="w-full">Guardar producto</x-primary-button>
        </form>

        <div class="mt-4 space-y-3">
            <details class="rounded-2xl border border-white/5 bg-[#1d1a2d] p-4">
                <summary class="cursor-pointer text-sm font-semibold text-[#d7c4f8]">＋ Agregar una bodega</summary>
                <form action="{{ route('inventory.warehouses.store') }}" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <x-form-input id="warehouse_name" name="name" label="Nombre de la bodega" :value="old('name')" required />
                    <x-form-input id="warehouse_code" name="code" label="Código único" :value="old('code')" required />
                    <x-form-input id="warehouse_address" name="address" label="Dirección" :value="old('address')" />
                    <x-primary-button class="w-full">Guardar bodega</x-primary-button>
                </form>
            </details>
            <details class="rounded-2xl border border-white/5 bg-[#1d1a2d] p-4">
                <summary class="cursor-pointer text-sm font-semibold text-[#d7c4f8]">＋ Agregar un proveedor</summary>
                <form action="{{ route('inventory.suppliers.store') }}" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <x-form-input id="supplier_name" name="name" label="Nombre del proveedor" :value="old('name')" required />
                    <x-form-input id="supplier_lead_time_days" name="lead_time_days" label="Plazo de entrega (días)" type="number" min="1" max="365" :value="old('lead_time_days', '7')" required />
                    <x-form-input id="supplier_contact_email" name="contact_email" label="Correo de contacto" type="email" :value="old('contact_email')" />
                    <x-primary-button class="w-full">Guardar proveedor</x-primary-button>
                </form>
            </details>
        </div>
    </main>
@endsection
