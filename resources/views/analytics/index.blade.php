@extends('layouts.app')

@section('title', 'Analítica de productos | Deepeer AI')

@section('content')
    <main class="mx-auto min-h-screen w-full max-w-[480px] px-4 pb-28 sm:px-6">
        <x-module-header title="Analítica" />
        <x-flash-message />

        <section class="mt-5 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[#eee9fa]">Analítica de productos</h1>
                <p class="mt-0.5 text-xs text-[#aaa2b6]">Rendimiento y velocidad de rotación</p>
            </div>
            <nav aria-label="Periodo de analítica" class="flex shrink-0 rounded-full bg-[#211d31] p-1">
                @foreach (['month' => 'Mes', 'season' => 'Temporada', 'year' => 'Año'] as $key => $label)
                    <a href="{{ route('analytics.index', ['period' => $key]) }}"
                       @class(['rounded-full px-2.5 py-2 text-[10px] font-semibold', 'bg-[#873cf1] text-white' => $period === $key, 'text-[#c7bfd2]' => $period !== $key])>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </section>

        <section class="mt-4 grid grid-cols-2 gap-3" aria-label="Indicadores clave">
            <article class="rounded-2xl border border-white/5 bg-[#211d31] p-4 shadow-lg shadow-black/10">
                <div class="flex items-center justify-between text-xs text-[#c7bfd2]">
                    <h2>Ventas registradas</h2>
                    <span aria-hidden="true" class="text-[#c9a8ff]">▣</span>
                </div>
                <p class="mt-3 text-[25px] font-bold leading-none text-[#eee9fa]">${{ number_format($sales, 0) }}</p>
                @if ($growth !== null)
                    <p class="mt-2 text-[10px] {{ $growth >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                        {{ $growth >= 0 ? '↗ +' : '↘ ' }}{{ number_format($growth, 1) }}%
                        <span class="text-[#aaa2b6]">vs. periodo anterior</span>
                    </p>
                @else
                    <p class="mt-2 text-[10px] text-[#aaa2b6]">Sin ventas previas para comparar</p>
                @endif
            </article>
            <article class="rounded-2xl border border-white/5 bg-[#211d31] p-4 shadow-lg shadow-black/10">
                <div class="flex items-center justify-between text-xs text-[#c7bfd2]">
                    <h2>Rotación stock</h2>
                    <span aria-hidden="true" class="text-[#c9a8ff]">◴</span>
                </div>
                <p class="mt-3 text-[25px] font-bold leading-none text-[#eee9fa]">{{ number_format($turnover, 1) }}x</p>
                <p class="mt-2 text-[10px] text-[#c9a8ff]">
                    @if ($rotationDays !== null)
                        Ciclo aprox. {{ $rotationDays }} días
                    @else
                        Sin datos de ventas
                    @endif
                </p>
            </article>
        </section>

        <section class="mt-4 rounded-2xl border border-white/5 bg-[#211d31] p-4 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-[#eee9fa]"><span class="mr-1 text-[#c9a8ff]">●</span> Velocidad mensual</h2>
                    <p class="mt-1 text-xs text-[#bcb5cb]">Unidades despachadas · últimos 6 meses</p>
                </div>
                <span class="rounded-full bg-[#30273f] px-2 py-1 text-[9px] font-semibold text-[#d5baff]">{{ number_format($unitsSold) }} uds.</span>
            </div>

            <div class="mt-6 flex h-40 items-end justify-around gap-2 border-b border-white/10 px-1">
                @foreach ($chart as $bar)
                    @php($barHeight = $bar['units'] > 0 ? max(12, (int) round(($bar['units'] / $peak) * 100)) : 3)
                    <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1.5">
                        @if ($bar['current'] && $trend !== null)
                            <span class="text-[9px] font-semibold text-[#d4baff]">{{ $trend >= 0 ? '+' : '' }}{{ number_format($trend, 0) }}%</span>
                        @endif
                        <div
                            class="w-full max-w-9 rounded-t-lg {{ $bar['current'] ? 'bg-gradient-to-t from-[#873cf1] to-[#d1a8ff] shadow-[0_0_15px_rgba(135,60,241,.28)]' : 'bg-gradient-to-t from-[#443863] to-[#8064bd]' }}"
                            style="height: {{ $barHeight }}%"
                            title="{{ $bar['label'] }}: {{ number_format($bar['units']) }} unidades"
                        ></div>
                        <span class="text-[10px] text-[#bcb5cb]">{{ $bar['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex items-center gap-2 rounded-xl bg-[#29243a] px-3 py-2.5 text-xs text-[#c7bfd2]">
                <span class="text-[#d1b4ff]" aria-hidden="true">✦</span>
                @if ($trend !== null)
                    Las salidas {{ $trend >= 0 ? 'subieron' : 'bajaron' }} {{ number_format(abs($trend), 1) }}% entre el primer y último mes.
                @else
                    Registra más salidas para comparar una tendencia.
                @endif
                @if ($forecastGrowth !== null)
                    Proyección lineal: {{ number_format($forecastUnits) }} unidades para el próximo mes ({{ $forecastGrowth >= 0 ? '+' : '' }}{{ number_format($forecastGrowth, 1) }}%).
                @else
                    Sin suficientes datos para estimar el próximo mes.
                @endif
                <span class="text-[#a99fba]">Estimación estadística local; no hay un proveedor externo de IA conectado.</span>
            </div>
        </section>

        <section class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-bold text-[#eee9fa]"><span class="mr-1 text-[#d0aaff]">♨</span> Más vendidos</h2>
                <a href="{{ route('inventory.index') }}" class="text-[10px] font-semibold text-[#d1b4ff]">Ver inventario →</a>
            </div>
            @if ($topProducts->isEmpty())
                <div class="rounded-2xl bg-[#1d1a2d] p-4 text-sm text-[#aaa2b6]">Todavía no hay salidas confirmadas en este periodo.</div>
            @else
                <div class="flex gap-3 overflow-x-auto pb-2 [scrollbar-width:none]">
                    @foreach ($topProducts as $index => $product)
                        <article class="min-w-[210px] flex-1 rounded-2xl border border-white/5 bg-[#211d31] p-3.5">
                            <div class="flex items-center justify-between">
                                <span class="rounded-full bg-[#4c2b83] px-2.5 py-1 text-[10px] font-bold text-[#e3d0ff]">#{{ $index + 1 }} top</span>
                                <span class="text-[10px] font-semibold text-emerald-300">↑</span>
                            </div>
                            <div class="mt-3 flex items-center gap-2.5">
                                <div class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#30283e] text-lg text-[#c8a8ff]">
                                    @if ($product->image)
                                        <img src="{{ asset('images/'.$product->image) }}" alt="{{ $product->name }}" class="size-full object-cover" loading="lazy">
                                    @else
                                        <span aria-hidden="true">▧</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate text-xs font-semibold text-[#eee9fa]">{{ $product->name }}</h3>
                                    <p class="mt-0.5 truncate text-[10px] text-[#aaa2b6]">SKU: {{ $product->sku }}</p>
                                </div>
                            </div>
                            <p class="mt-3 flex justify-between text-[10px] text-[#aaa2b6]">
                                <span>Unidades despachadas</span>
                                <strong class="text-sm text-[#e7e0f2]">{{ number_format($product->units_sold) }}</strong>
                            </p>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="mt-5">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-bold text-[#eee9fa]"><span class="mr-1 text-orange-300">▤</span> Sin movimiento</h2>
                <span class="rounded-full bg-rose-500/15 px-2.5 py-1 text-[10px] font-bold text-rose-200">{{ $stagnantProducts->count() }} productos</span>
            </div>
            @forelse ($stagnantProducts as $product)
                @php($daysIdle = $product->last_sale_at ? max(0, (int) \Carbon\Carbon::parse($product->last_sale_at)->diffInDays(now())) : null)
                <article class="mb-2.5 rounded-2xl border border-white/5 bg-[#211d31] p-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#30283e] text-lg text-[#c8a8ff]">
                            @if ($product->image)
                                <img src="{{ asset('images/'.$product->image) }}" alt="{{ $product->name }}" class="size-full object-cover" loading="lazy">
                            @else
                                <span aria-hidden="true">▧</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-xs font-semibold text-[#eee9fa]">{{ $product->name }}</h3>
                            <p class="text-[10px] text-[#aaa2b6]">SKU: {{ $product->sku }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-rose-500/15 px-2 py-1 text-[9px] font-semibold text-rose-200">
                            {{ $daysIdle === null ? 'Sin historial' : "{$daysIdle} días" }}
                        </span>
                    </div>
                    <p class="mt-3 flex justify-between rounded-lg bg-[#29243a] px-2.5 py-2 text-[10px] text-[#c7bfd2]">
                        <span>Capital inmovilizado</span>
                        <strong class="text-[#eee9fa]">{{ number_format($product->current_stock) }} uds. <span class="text-rose-200">(${{ number_format($product->current_stock * (float) $product->cost, 2) }})</span></strong>
                    </p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <a href="{{ route('assistant.index', ['prompt' => "Sugiere una promoción para {$product->sku}"]) }}" class="rounded-xl bg-[#873cf1] px-2 py-2 text-center text-[10px] font-semibold text-white hover:bg-[#974fff]">✧ Sugerir promoción</a>
                        <a href="{{ route('assistant.index', ['prompt' => "Analiza una liquidación rápida para {$product->sku}"]) }}" class="rounded-xl bg-[#383149] px-2 py-2 text-center text-[10px] font-semibold text-[#d4cce0] hover:bg-[#443a58]">▤ Analizar liquidación</a>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl bg-[#1d1a2d] p-4 text-sm text-[#aaa2b6]">No hay productos con existencias inmovilizadas durante 30 días o más.</p>
            @endforelse
        </section>
    </main>
    <x-module-nav active="analytics" />
@endsection
