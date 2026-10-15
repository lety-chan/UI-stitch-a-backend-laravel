@extends('layouts.app')

@section('title', 'Inventario global | Deepeer AI')

@section('content')
    <main class="mx-auto min-h-screen w-full max-w-[480px] px-4 pb-28 sm:px-6">
        <x-module-header title="Inventario" />
        <x-flash-message />

        <section class="mt-5">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-[#eee9fa]">Inventario Global</h1>
                    <p class="mt-0.5 text-xs text-[#aaa2b6]">{{ number_format($metrics['items']) }} unidades · {{ number_format($metrics['products']) }} SKUs</p>
                </div>
                <a href="{{ route('assistant.index') }}" class="rounded-xl bg-[#1d1a2d] px-3 py-2 text-xs font-medium text-[#d4c2f0]">✺ Preguntar IA</a>
            </div>

            <form action="{{ route('inventory.index') }}" method="GET" class="mt-4 flex gap-2 rounded-2xl border border-white/5 bg-[#1d1a2d] px-3 py-3 focus-within:border-[#8b5cf6]/60">
                <label for="inventory-search" class="sr-only">Buscar por nombre de producto, SKU o bodega</label>
                <span aria-hidden="true" class="text-[#aaa2b6]">⌕</span>
                <input id="inventory-search" name="search" value="{{ $search }}" placeholder="Buscar producto, SKU o bodega..." class="min-w-0 flex-1 bg-transparent text-sm text-[#eee9fa] outline-none placeholder:text-[#898294]">
                @if ($filter !== '')
                    <input type="hidden" name="filter" value="{{ $filter }}">
                @endif
                <button type="submit" aria-label="Buscar" class="text-xs font-semibold text-[#c9a8ff]">Buscar</button>
            </form>

            <div class="mt-3 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none]">
                @foreach ([['value' => '', 'label' => 'Todos'], ['value' => 'critical', 'label' => 'Stock crítico'], ['value' => 'central', 'label' => 'Bodega central']] as $tab)
                    <a href="{{ route('inventory.index', array_filter(['filter' => $tab['value'], 'search' => $search ?: null])) }}"
                       @class(['shrink-0 rounded-full px-3.5 py-2 text-xs font-semibold', 'bg-[#873cf1] text-white' => $filter === $tab['value'], 'bg-[#211d31] text-[#c5bdce]' => $filter !== $tab['value']])>
                        {{ $tab['label'] }}
                    </a>
                @endforeach
                @foreach ($warehouses as $warehouse)
                    <a href="{{ route('inventory.index', ['filter' => 'warehouse:'.$warehouse->id]) }}"
                       @class(['shrink-0 rounded-full px-3.5 py-2 text-xs font-semibold', 'bg-[#873cf1] text-white' => $filter === 'warehouse:'.$warehouse->id, 'bg-[#211d31] text-[#c5bdce]' => $filter !== 'warehouse:'.$warehouse->id])>
                        {{ $warehouse->name }}
                    </a>
                @endforeach
            </div>
        </section>

        <section aria-label="Métricas de inventario" class="mt-4 grid grid-cols-2 gap-3">
            <article class="rounded-2xl border border-white/5 bg-[#211d31] p-4 shadow-lg shadow-black/10">
                <p class="text-xs text-[#c7bfd2]">Productos activos</p>
                <p class="mt-1 text-2xl font-bold text-[#eee9fa]">{{ number_format($metrics['products']) }}</p>
                <p class="mt-1 text-[11px] text-[#aaa2b6]">{{ number_format($metrics['items']) }} unidades en inventario</p>
            </article>
            <article class="rounded-2xl border border-rose-400/10 bg-[#211d31] p-4 shadow-lg shadow-black/10">
                <p class="flex items-center gap-1.5 text-xs text-[#ffc0c2]">Stock crítico <span aria-hidden="true">⚠</span></p>
                <p class="mt-1 text-2xl font-bold text-rose-200">{{ number_format($metrics['critical']) }}</p>
                <p class="mt-1 text-[11px] text-[#aaa2b6]">productos en su mínimo o menos</p>
            </article>
        </section>

        <div class="mt-6 flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wide text-[#c5bdce]">Artículos prioritarios</h2>
            <span class="text-[10px] text-[#aaa2b6]">Stock bajo primero</span>
        </div>

        <section class="mt-3 space-y-2.5">
            @forelse ($products as $product)
                @php
                    $stockLevel = $product->stock_level;
                    $stockLabels = [
                        'critical' => 'Crítico',
                        'running_low' => 'Agotándose',
                        'optimal' => 'Óptimo',
                        'regular' => 'Regular',
                        'abundant' => 'Abundante',
                    ];
                    $stockColors = [
                        'critical' => 'bg-rose-500/15 text-rose-200 ring-1 ring-rose-500/20',
                        'running_low' => 'bg-amber-400/15 text-amber-200',
                        'optimal' => 'bg-emerald-400/15 text-emerald-200',
                        'regular' => 'bg-violet-400/15 text-violet-200',
                        'abundant' => 'bg-[#373145] text-[#c9c0d2]',
                    ];
                @endphp
                <article class="rounded-2xl border border-white/5 bg-[#1d1a2d] p-3.5 shadow-lg shadow-black/10">
                    <div class="flex items-center gap-3">
                        <div class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-[#29243a] text-xl text-[#a889cf]">
                            @if ($product->image)
                                <img src="{{ asset('images/'.$product->image) }}" alt="{{ $product->name }}" class="size-full object-cover" loading="lazy">
                            @else
                                <span aria-hidden="true">▧</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-semibold text-[#eee9fa]">{{ $product->name }}</h3>
                            <p class="mt-1 truncate text-[10px] font-medium uppercase tracking-wide text-[#aaa2b6]">SKU: {{ $product->sku }}</p>
                            <p class="mt-1 text-xs font-semibold text-[#d7c4f8]">${{ number_format((float) $product->price, 2) }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span @class(['rounded-full px-2.5 py-1 text-[10px] font-semibold', $stockColors[$stockLevel]])>
                                {{ $stockLabels[$stockLevel] }}
                            </span>
                            <p class="mt-2 text-xs font-bold text-[#eee9fa]">{{ number_format($product->current_stock) }} uds.</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-2 rounded-xl bg-[#29243a] px-3 py-2 text-[10px]">
                        <span class="min-w-0 truncate text-[#c9c1d2]">
                            <span class="mr-1 text-[#d1b4ff]" aria-hidden="true">⌂</span>
                            {{ $product->warehouse?->name ?? 'Sin bodega asignada' }}
                            @if ($product->location_details)
                                · {{ $product->location_details }}
                            @endif
                        </span>
                        <span class="shrink-0 text-[#aaa2b6]">Mín. {{ number_format($product->minimum_stock) }}</span>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-white/10 bg-[#1d1a2d] px-5 py-10 text-center">
                    <p class="text-sm font-semibold text-[#d7c4f8]">{{ $search ? 'No encontramos productos para esa búsqueda.' : 'Todavía no hay productos en el inventario.' }}</p>
                    <p class="mt-1 text-xs text-[#aaa2b6]">Agrega un producto para comenzar a registrar existencias y analizar la demanda.</p>
                    <a href="{{ route('inventory.create') }}" class="mt-4 inline-flex rounded-xl bg-[#873cf1] px-4 py-2.5 text-sm font-bold text-white">Agregar producto</a>
                </div>
            @endforelse
        </section>
        <div class="mt-4">{{ $products->links() }}</div>

        <a href="{{ route('inventory.create') }}" aria-label="Agregar producto" class="fixed bottom-24 right-[max(1.5rem,calc((100vw-432px)/2))] z-20 grid size-14 place-items-center rounded-full bg-[#873cf1] text-3xl text-white shadow-[0_8px_30px_rgba(135,60,241,.5)] transition hover:scale-105">+</a>
    </main>
    <x-module-nav active="inventory" />
@endsection
