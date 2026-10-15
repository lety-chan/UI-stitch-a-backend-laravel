@extends('layouts.app')

@section('title', 'Asistente Deepeer AI | Deepeer AI')

@section('content')
    <main class="mx-auto flex min-h-screen w-full max-w-[480px] flex-col px-4 pb-32 sm:px-6">
        <x-module-header title="Asistente Deepeer" eyebrow="DEEPEER AI" />
        <x-flash-message />
        @if ($errors->any())
            <p role="alert" class="mt-3 rounded-xl border border-rose-400/20 bg-rose-400/10 px-4 py-3 text-sm text-rose-200">{{ $errors->first() }}</p>
        @endif

        <section class="mt-4 flex items-center justify-between gap-2 rounded-2xl bg-[#1d1a2d] px-3 py-2.5">
            <div class="flex min-w-0 items-center gap-2">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-[#382651] text-lg text-[#d2b8ff]">✺</span>
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-bold text-[#eee9fa]">Asistente de inventario</h1>
                    <p class="truncate text-[9px] text-[#bcb5cb]"><span class="text-emerald-300">●</span> Interpretación local · confirma antes de aplicar</p>
                </div>
            </div>
            <a href="{{ route('assistant.index') }}" class="shrink-0 rounded-full bg-[#302b3d] px-2.5 py-1.5 text-[9px] font-semibold text-[#c6bfd0]">Nueva sesión</a>
        </section>

        <section aria-label="Conversación con el asistente" class="mt-4 flex-1 space-y-4">
            <p class="mx-auto w-fit rounded-full bg-[#1d1a2d] px-3 py-1.5 text-[9px] text-[#aaa2b6]">Los movimientos requieren confirmación</p>
            @if ($messages->isEmpty())
                <div class="flex gap-2">
                    <span class="mt-1 grid size-7 shrink-0 place-items-center rounded-full bg-[#873cf1] text-xs text-white">✺</span>
                    <div class="max-w-[88%] rounded-2xl rounded-tl-sm bg-[#e9d8ff] px-3.5 py-3 text-sm leading-5 text-[#2a1d3e]">
                        Hola, {{ auth()->user()->name }}. Puedo preparar entradas y salidas de stock para que las revises, o estimar cobertura a partir del historial de ventas confirmado.
                        <p class="mt-2 text-[10px] text-[#655477]">No hay un proveedor externo de IA configurado: las respuestas se generan con reglas locales.</p>
                    </div>
                </div>
            @endif

            @foreach ($messages as $message)
                <article @class(['flex gap-2', 'justify-end' => $message->sender === 'user'])>
                    @if ($message->sender === 'ai')
                        <span class="mt-1 grid size-7 shrink-0 place-items-center rounded-full bg-[#873cf1] text-xs text-white" aria-hidden="true">✺</span>
                    @endif
                    <div @class([
                        'max-w-[88%] rounded-2xl px-3.5 py-3 text-sm leading-5',
                        'rounded-br-sm bg-[#373247] text-[#eee9fa]' => $message->sender === 'user',
                        'rounded-tl-sm bg-[#e9d8ff] text-[#2a1d3e]' => $message->sender === 'ai',
                    ])>
                        <p class="whitespace-pre-line">{{ $message->message }}</p>

                        @if (($message->metadata_json['type'] ?? null) === 'movement_confirmation')
                            @php($movement = $movementCards[$message->metadata_json['movement_id']] ?? null)
                            @if ($movement)
                                <section class="mt-3 overflow-hidden rounded-xl border border-[#8b5cf6]/30 bg-[#221d32] text-[#eee9fa]">
                                    <div class="border-t-2 border-[#a574ff] p-3">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <h2 class="text-sm font-bold">▤ Confirmación de movimiento</h2>
                                                <p class="mt-0.5 text-[9px] text-[#bcb5cb]">Revisa los datos antes de guardar</p>
                                            </div>
                                            <span class="rounded-full bg-[#373145] px-2 py-1 text-[8px] font-semibold uppercase text-[#d5baff]">{{ $movement->status }}</span>
                                        </div>
                                        <dl class="mt-3 space-y-2 rounded-lg bg-[#302a40] p-2.5 text-[10px]">
                                            <div class="flex justify-between gap-2"><dt class="text-[#bcb5cb]">Producto</dt><dd class="text-right font-semibold">{{ $movement->product->name }} · {{ $movement->product->sku }}</dd></div>
                                            <div class="flex justify-between gap-2"><dt class="text-[#bcb5cb]">Stock actual</dt><dd class="font-semibold">{{ number_format($movement->previous_stock) }} unidades</dd></div>
                                            <div class="flex justify-between gap-2"><dt class="text-[#bcb5cb]">Ajuste {{ $movement->type === 'in' ? 'recibido' : 'despachado' }}</dt><dd class="font-semibold">{{ $movement->type === 'in' ? '+' : '−' }}{{ number_format($movement->quantity) }} → {{ number_format($movement->new_stock) }} uds.</dd></div>
                                            <div class="flex justify-between gap-2"><dt class="text-[#bcb5cb]">Ubicación</dt><dd class="text-right">{{ $movement->warehouse?->name ?? 'Sin bodega' }}@if ($movement->product->location_details) · {{ $movement->product->location_details }}@endif</dd></div>
                                            <div class="flex justify-between gap-2"><dt class="text-[#bcb5cb]">Operador</dt><dd>{{ $movement->user?->name ?? 'Usuario' }}</dd></div>
                                        </dl>
                                        @if ($movement->status === 'pending')
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                <form action="{{ route('assistant.movements.cancel', $movement) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="w-full rounded-lg bg-[#373247] px-2 py-2 text-[10px] font-semibold text-[#d2c9df]">Cancelar</button>
                                                </form>
                                                <form action="{{ route('assistant.movements.confirm', $movement) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="w-full rounded-lg bg-[#873cf1] px-2 py-2 text-[10px] font-bold text-white">✓ Confirmar</button>
                                                </form>
                                            </div>
                                        @elseif ($movement->status === 'confirmed')
                                            <p class="mt-2 rounded-lg bg-emerald-500/10 px-2 py-1.5 text-center text-[10px] font-semibold text-emerald-200">Movimiento aplicado · {{ $movement->new_stock }} unidades</p>
                                        @else
                                            <p class="mt-2 rounded-lg bg-[#373247] px-2 py-1.5 text-center text-[10px] font-semibold text-[#c6bfd0]">Movimiento cancelado · sin cambios de stock</p>
                                        @endif
                                    </div>
                                </section>
                            @endif
                        @elseif (($message->metadata_json['type'] ?? null) === 'demand_analysis')
                            <div class="mt-2 rounded-lg bg-[#302a40] p-2.5 text-[10px] text-[#ddd3ed]">
                                ✧ ANÁLISIS DE DEMANDA · {{ $message->metadata_json['units_sold_30_days'] }} salidas confirmadas en 30 días
                            </div>
                        @endif
                        <p class="mt-2 text-right text-[9px] opacity-60">{{ $message->created_at?->format('H:i') }}</p>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="mt-5">
            <p class="mb-2 text-[10px] font-semibold text-[#c5bdce]">Accesos rápidos</p>
            <div class="flex gap-2 overflow-x-auto pb-2 [scrollbar-width:none]">
                @foreach ([
                    'Auditar lote' => 'Ayúdame a auditar el lote SKU-',
                    'Generar remisión' => 'Prepara una remisión para SKU-',
                    'Proyectar demanda' => 'Proyecta la cobertura del producto ',
                ] as $label => $prompt)
                    <button type="button" data-chat-prompt="{{ $prompt }}" class="shrink-0 rounded-full bg-[#29243a] px-3 py-2 text-[9px] font-medium text-[#d6c5f2] hover:bg-[#352a47]">✧ {{ $label }}</button>
                @endforeach
            </div>

            <form id="assistant-form" action="{{ route('assistant.send') }}" method="POST" class="rounded-2xl border border-white/5 bg-[#211d31] p-2">
                @csrf
                <label for="assistant-message" class="sr-only">Escribe una orden o consulta</label>
                <textarea id="assistant-message" name="message" rows="2" maxlength="2000" required placeholder="Escribe una orden o consulta…" class="w-full resize-none bg-transparent px-2 pt-1 text-sm text-[#eee9fa] outline-none placeholder:text-[#898294]">{{ old('message', request('prompt')) }}</textarea>
                <div class="flex items-center justify-between gap-2 border-t border-white/5 pt-2">
                    <div class="flex items-center gap-1.5">
                        <span aria-hidden="true" class="px-2 text-[#aaa2b6]">⌁</span>
                        <button id="voice-input" type="button" class="grid size-8 place-items-center rounded-lg text-[#c6bfd0] hover:bg-white/5" aria-label="Dictar mensaje" title="Dictar mensaje">♩</button>
                        <span id="voice-status" class="text-[9px] text-[#aaa2b6]" role="status"></span>
                    </div>
                    <button type="submit" aria-label="Enviar consulta" class="grid size-9 place-items-center rounded-xl bg-[#873cf1] text-lg text-white shadow-md shadow-[#873cf1]/20 hover:bg-[#974fff]">➤</button>
                </div>
            </form>
            <p class="mt-2 text-center text-[9px] text-[#837b91]">Entrada por voz usa el reconocimiento de voz integrado del navegador.</p>
        </section>
    </main>
    <x-module-nav active="assistant" />
@endsection
