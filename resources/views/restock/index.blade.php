@extends('layouts.app')

@section('title', 'Reabastecimiento inteligente | Deepeer AI')

@section('content')
    <main class="mx-auto min-h-screen w-full max-w-[480px] px-4 pb-28 sm:px-6">
        <x-module-header title="Reabastecer" />
        <x-flash-message />
        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-200">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="mt-5">
            <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-[#c5a4fb]">✦ Recomendaciones basadas en salidas de 30 días</p>
            <h1 class="mt-1 text-xl font-bold tracking-tight text-[#eee9fa]">Reabastecimiento inteligente</h1>
            <p class="mt-1 text-xs text-[#aaa2b6]">Los estimados son referenciales y usan los movimientos confirmados.</p>
        </section>

        <section class="mt-4 rounded-2xl border border-rose-400/15 bg-[radial-gradient(ellipse_at_top_left,_rgba(129,51,157,.22),_transparent_70%),#211d31] p-4">
            <div class="flex items-start gap-3">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-[#4b2c73] text-lg text-[#dfc6ff]" aria-hidden="true">◉</span>
                <div>
                    <span class="rounded-full bg-rose-500/15 px-2 py-1 text-[9px] font-bold uppercase tracking-wide text-rose-200">Riesgo de agotamiento</span>
                    <h2 class="mt-2 text-sm font-bold text-[#eee9fa]">{{ $recommendations->count() }} {{ $recommendations->count() === 1 ? 'producto necesita' : 'productos necesitan' }} atención</h2>
                    <p class="mt-1 text-xs leading-5 text-[#bcb5cb]">
                        @if ($recommendations->isNotEmpty())
                            Revisa el plazo de entrega de cada proveedor antes de aprobar las órdenes.
                        @else
                            No hay productos activos bajo su mínimo ni en riesgo según el historial disponible.
                        @endif
                    </p>
                </div>
            </div>
        </section>

        <section class="mt-3 grid grid-cols-2 gap-2.5">
            <article class="rounded-2xl border border-white/5 bg-[#211d31] p-3.5">
                <p class="text-[10px] text-[#bcb5cb]">Inversión sugerida</p>
                <p class="mt-1 text-xl font-bold text-[#eee9fa]">${{ number_format($investment, 2) }}</p>
                <p class="mt-1 text-[9px] text-emerald-300">Sugerencias sin orden abierta</p>
            </article>
            <article class="rounded-2xl border border-white/5 bg-[#211d31] p-3.5">
                <p class="text-[10px] text-[#bcb5cb]">Órdenes pendientes</p>
                <p class="mt-1 text-xl font-bold text-[#eee9fa]">{{ $pendingCount }}</p>
                <p class="mt-1 text-[9px] text-[#c9a8ff]">Esperando autorización</p>
            </article>
        </section>

        <form action="{{ route('restock.toggle-auto-approve') }}" method="POST" class="mt-3 flex items-center justify-between gap-3 rounded-2xl border border-white/5 bg-[#211d31] px-3.5 py-3">
            @csrf
            <div>
                <p class="text-xs font-semibold text-[#eee9fa]">Autoaprobar sugerencias</p>
                <p class="mt-0.5 text-[10px] text-[#aaa2b6]">Las órdenes nuevas se crearán como aprobadas.</p>
            </div>
            <button type="submit" role="switch" aria-checked="{{ $autoApprove ? 'true' : 'false' }}" aria-label="Alternar aprobación automática" @class(['relative h-6 w-11 shrink-0 rounded-full transition-colors', 'bg-[#873cf1]' => $autoApprove, 'bg-[#454052]' => ! $autoApprove])>
                <span @class(['absolute top-1 size-4 rounded-full bg-white transition-all', 'left-6' => $autoApprove, 'left-1' => ! $autoApprove])></span>
            </button>
        </form>

        <section class="mt-6">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-bold text-[#eee9fa]">✦ Recomendaciones críticas</h2>
                <span class="text-[9px] text-[#aaa2b6]">{{ $recommendations->count() }} sugerencias</span>
            </div>
            @forelse ($recommendations as $recommendation)
                @php($product = $recommendation['product'])
                <article class="mt-3 rounded-2xl border border-white/5 bg-[#211d31] p-3.5 shadow-lg shadow-black/10">
                    <div class="flex gap-2.5">
                        <div class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-[#30283e] text-lg text-[#d0aeff]">
                            @if ($product->image)
                                <img src="{{ asset('images/'.$product->image) }}" alt="{{ $product->name }}" class="size-full object-cover" loading="lazy">
                            @else
                                <span aria-hidden="true">▧</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                @if ($recommendation['has_open_order'])
                                    <span class="rounded-full bg-[#373145] px-2 py-0.5 text-[9px] font-semibold text-[#d5baff]">Orden abierta</span>
                                @else
                                    <span class="rounded-full bg-rose-500/15 px-2 py-0.5 text-[9px] font-bold text-rose-200">{{ $product->current_stock <= $product->minimum_stock ? 'Crítico' : 'En riesgo' }}</span>
                                @endif
                                <span class="truncate text-[9px] text-[#aaa2b6]">SKU: {{ $product->sku }}</span>
                            </div>
                            <h3 class="mt-1 truncate text-xs font-bold text-[#eee9fa]">{{ $product->name }}</h3>
                            <p class="mt-0.5 truncate text-[10px] text-[#bcb5cb]">{{ $product->supplier?->name ?? 'Sin proveedor asignado' }}</p>
                        </div>
                        <div class="shrink-0 self-start rounded-full bg-[#eee0ff] px-2 py-1 text-[9px] font-bold text-[#2b1745]">Pedir {{ number_format($recommendation['quantity']) }}</div>
                    </div>

                    <div class="mt-3 rounded-xl bg-[#29243a] p-3">
                        <div class="flex justify-between gap-2 text-[10px]">
                            <span class="text-[#bcb5cb]">Stock actual: <strong class="text-[#eee9fa]">{{ number_format($product->current_stock) }} uds.</strong></span>
                            <span class="{{ $recommendation['days_remaining'] !== null && $recommendation['days_remaining'] <= $recommendation['lead_time'] ? 'text-rose-200' : 'text-[#d3c4ea]' }}">
                                @if ($recommendation['days_remaining'] === null)
                                    Sin historial de salidas
                                @else
                                    Quedan {{ $recommendation['days_remaining'] }} días
                                @endif
                            </span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[#454052]">
                            @php($coverWidth = $recommendation['days_remaining'] === null ? 20 : min(100, max(8, (int) round($recommendation['days_remaining'] / max(1, $recommendation['lead_time'] + 14) * 100))))
                            <span class="block h-full rounded-full {{ $recommendation['days_remaining'] !== null && $recommendation['days_remaining'] <= $recommendation['lead_time'] ? 'bg-rose-400' : 'bg-gradient-to-r from-[#873cf1] to-[#d0aaff]' }}" style="width: {{ $coverWidth }}%"></span>
                        </div>
                        <div class="mt-2 flex justify-between gap-2 text-[9px] text-[#aaa2b6]">
                            <span>Entrega: {{ $recommendation['lead_time'] }} días</span>
                            <span>Quiebre:
                                {{ $recommendation['break_date']?->locale('es')->translatedFormat('d M') ?? 'Sin estimar' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-2">
                        <div>
                            <p class="text-[9px] uppercase tracking-wide text-[#aaa2b6]">Costo estimado</p>
                            <p class="text-sm font-bold text-[#eee9fa]">${{ number_format($recommendation['estimated_cost'], 2) }}</p>
                        </div>
                        @if ($recommendation['has_open_order'])
                            <span class="rounded-xl bg-[#373145] px-3 py-2.5 text-[10px] font-semibold text-[#c6bfd0]">Orden ya creada</span>
                        @elseif ($product->supplier)
                            <form action="{{ route('restock.orders.store', $product) }}" method="POST">
                                @csrf
                                <button type="submit" class="rounded-xl bg-[#873cf1] px-3 py-2.5 text-[10px] font-bold text-white shadow-md shadow-[#873cf1]/20 hover:bg-[#974fff]">▤ Generar orden</button>
                            </form>
                        @elseif ($suppliers->isNotEmpty())
                            <form action="{{ route('restock.products.supplier', $product) }}" method="POST" class="flex max-w-[205px] items-center gap-1.5">
                                @csrf
                                <label for="supplier-{{ $product->id }}" class="sr-only">Proveedor para {{ $product->name }}</label>
                                <select id="supplier-{{ $product->id }}" name="supplier_id" required class="min-w-0 rounded-lg bg-[#302a40] px-2 py-2 text-[9px] text-[#eee9fa]">
                                    <option value="">Proveedor…</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="shrink-0 rounded-lg bg-[#873cf1] px-2 py-2 text-[9px] font-bold text-white">Asignar</button>
                            </form>
                        @else
                            <a href="{{ route('inventory.create') }}" class="rounded-xl bg-[#373145] px-3 py-2 text-[9px] font-semibold text-[#e0d5f0]">Crear proveedor</a>
                        @endif
                    </div>
                </article>
            @empty
                <p class="mt-3 rounded-2xl bg-[#1d1a2d] p-4 text-sm text-[#aaa2b6]">Las existencias actuales cubren el mínimo de tus productos. Registra salidas para mejorar las estimaciones de demanda.</p>
            @endforelse
        </section>

        @if ($recommendations->isNotEmpty())
            <form action="{{ route('restock.approve-all') }}" method="POST" class="mt-4 rounded-2xl border border-[#6437a0]/30 bg-[#251c38] p-3.5">
                @csrf
                <p class="text-xs font-bold text-[#e8def7]">Aprobación rápida</p>
                <p class="mt-1 text-[10px] leading-4 text-[#bcb5cb]">Crea órdenes aprobadas para productos bajo su mínimo, con proveedor y sin órdenes abiertas. Revisa los costos antes de confirmar.</p>
                <button type="submit" @disabled($bulkInvestment <= 0) class="mt-3 w-full rounded-xl bg-[#873cf1] px-3 py-3 text-xs font-bold text-white hover:bg-[#974fff] disabled:cursor-not-allowed disabled:opacity-50">Aprobar órdenes críticas · ${{ number_format($bulkInvestment, 2) }}</button>
            </form>
        @endif

        @if ($openOrders->isNotEmpty())
            <section class="mt-6">
                <h2 class="text-sm font-bold text-[#eee9fa]">Órdenes abiertas</h2>
                @foreach ($openOrders as $order)
                    <article class="mt-2 rounded-xl bg-[#211d31] p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-xs font-semibold text-[#e8def7]">Orden #{{ $order->id }} · {{ $order->supplier?->name ?? 'Sin proveedor' }}</p>
                                <p class="mt-0.5 text-[10px] text-[#aaa2b6]">{{ $order->items->sum('quantity') }} unidades · ${{ number_format((float) $order->total_cost, 2) }}</p>
                            </div>
                            <span class="rounded-full bg-[#373145] px-2 py-1 text-[9px] font-semibold text-[#d5baff]">{{ ucfirst($order->status) }}</span>
                        </div>
                        @if (in_array($order->status, ['suggested', 'pending'], true))
                            <form action="{{ route('restock.orders.approve', $order) }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="w-full rounded-lg bg-[#34284b] px-3 py-2 text-[10px] font-semibold text-[#dfcaff]">Aprobar orden</button>
                            </form>
                        @elseif ($order->status === 'approved')
                            <form action="{{ route('restock.orders.receive', $order) }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="w-full rounded-lg bg-emerald-600/20 px-3 py-2 text-[10px] font-semibold text-emerald-200">Registrar recepción y aumentar stock</button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </section>
        @endif
    </main>
    <x-module-nav active="restock" />
@endsection
