@extends('layouts.app')

@section('title', 'Perfil | Depeeper')

@section('content')
    <main class="mx-auto min-h-screen w-full max-w-[480px] px-4 pb-28 pt-5 sm:px-6">
        <header class="flex items-center justify-between gap-3">
            <a href="{{ route('welcome') }}" class="flex min-w-0 items-center gap-2">
                <span class="grid size-9 place-items-center rounded-xl bg-[#873cf1] text-lg font-bold text-white" aria-hidden="true">✦</span>
                <span class="min-w-0">
                    <span class="block text-[10px] font-bold uppercase tracking-[0.18em] text-[#c5a4fb]">Depeeper IA</span>
                    <span class="block text-base font-bold leading-tight text-[#eee9fa]">Perfil</span>
                </span>
            </a>
            <div class="flex items-center gap-3">
                <span class="text-xl text-[#c6bdd5]" aria-hidden="true">⌗</span>
                <span class="grid size-10 place-items-center overflow-hidden rounded-full border-2 border-[#a579ff] bg-[#332348] text-sm font-bold text-white">
                    @if (file_exists(public_path('images/avatar-carlos.jpg')))
                        <img src="{{ asset('images/avatar-carlos.jpg') }}" alt="Foto de {{ $user->name }}" class="size-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    @endif
                </span>
            </div>
        </header>

        @if (session('status'))
            <p role="status" class="mt-4 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">{{ session('status') }}</p>
        @endif

        <section class="mt-5 rounded-[24px] bg-[radial-gradient(ellipse_at_top_left,_rgba(131,67,201,0.28),_transparent_65%),#211b32] px-5 py-5 text-center shadow-lg shadow-black/10">
            <div class="relative mx-auto grid size-20 place-items-center rounded-full border-[3px] border-[#b88cff] bg-[#332348] text-2xl font-bold text-white">
                @if (file_exists(public_path('images/avatar-carlos.jpg')))
                    <img src="{{ asset('images/avatar-carlos.jpg') }}" alt="Foto de {{ $user->name }}" class="size-full rounded-full object-cover">
                @else
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                @endif
                <span class="absolute bottom-0 right-0 size-4 rounded-full border-[3px] border-[#211b32] bg-emerald-400"></span>
            </div>
            <h1 class="mt-3 text-xl font-bold text-[#f1edff]">{{ $user->name }}</h1>
            <p class="mt-0.5 text-sm leading-5 text-[#c7bfd2]">
                {{ $user->company_name ?: 'Cuenta Depeeper' }}<br>
                {{ $user->email }}
            </p>
            <span class="mt-3 inline-flex rounded-full bg-[#40276a] px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-[#e1cdff]">Cuenta activa</span>
        </section>

        <section class="mt-5 rounded-2xl bg-[#1d1a2d] p-4 shadow-lg shadow-black/10">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-bold text-[#e7e0f2]">Uso del ciclo actual</h2>
                <span class="text-[10px] font-semibold text-[#c5a4fb]">Sin actividad registrada</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs">
                <span class="flex items-center gap-2 text-[#c9c2d2]"><span class="text-[#d1b4ff]" aria-hidden="true">✺</span> Consultas IA</span>
                <span class="font-semibold text-[#eee9fa]">0 / 1,000</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-[#373145]">
                <span class="block h-full w-0 rounded-full bg-gradient-to-r from-[#873cf1] to-[#d1a8ff]"></span>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="rounded-xl bg-[#29243a] p-3">
                    <p class="text-[11px] text-[#c4bdce]"><span aria-hidden="true">⌂</span> Bodegas</p>
                    <p class="mt-1 text-lg font-bold leading-tight text-[#f1edff]">0 / 3</p>
                    <p class="mt-1 text-xs text-[#aaa2b6]">Sin conectar</p>
                </div>
                <div class="rounded-xl bg-[#29243a] p-3">
                    <p class="text-[11px] text-[#c4bdce]"><span aria-hidden="true">⟳</span> SKUs en vivo</p>
                    <p class="mt-1 text-lg font-bold leading-tight text-[#f1edff]">—</p>
                    <p class="mt-1 text-xs text-[#aaa2b6]">Sin sincronizar</p>
                </div>
            </div>
        </section>

        <section class="mt-6">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-[#eee9fa]">Planes y suscripción</h2>
                <span class="text-[10px] font-medium text-[#aaa2b6]">Opciones disponibles</span>
            </div>
            <article class="rounded-2xl bg-[#1d1a2d] p-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-[#bdb5cb]">Plan base</p>
                    <span class="rounded-full bg-[#353047] px-2.5 py-1 text-[10px] font-semibold text-[#cbc4d4]">Mensual</span>
                </div>
                <h3 class="mt-1 text-lg font-semibold text-[#e4def1]">Plan Mensual</h3>
                <p class="text-xs text-[#bcb5cb]">Suscripción no configurada</p>
                <p class="mt-2 text-3xl font-bold text-[#eee9fa]">$19<span class="ml-1 text-xs font-normal text-[#bcb5cb]">/mes</span></p>
                <ul class="mt-3 space-y-1.5 text-xs text-[#c6bfd0]">
                    <li><span class="mr-2 text-[#d6c5f2]" aria-hidden="true">✓</span>Hasta 3 bodegas simultáneas</li>
                    <li><span class="mr-2 text-[#d6c5f2]" aria-hidden="true">✓</span>1,000 consultas Depeeper IA/mes</li>
                </ul>
            </article>

            <article class="mt-4 rounded-[22px] border border-[#6233a4]/60 bg-[radial-gradient(ellipse_at_top_left,_rgba(135,60,241,0.30),_transparent_65%),#281b49] p-4 shadow-[0_10px_35px_rgba(81,35,136,0.18)]">
                <div class="flex items-center justify-between gap-2">
                    <span class="rounded-full bg-[#eedfff] px-2.5 py-1 text-[10px] font-bold text-[#21122f]">☆ Recomendado por IA</span>
                    <span class="text-2xl font-bold text-[#e4d3ff]">$190<span class="ml-1 text-[10px] font-medium text-[#c8b6e3]">/año</span></span>
                </div>
                <h3 class="mt-3 text-2xl font-bold tracking-tight text-[#f0e7ff]">Plan Anual</h3>
                <span class="mt-1 inline-flex rounded bg-[#4a2b78] px-2 py-0.5 text-[10px] font-semibold text-[#e2d1ff]">Ahorra 20% anual</span>
                <ul class="mt-4 space-y-2.5 text-sm leading-5 text-[#ded5ed]">
                    <li class="flex gap-2"><span class="text-[#d2b8ff]" aria-hidden="true">✓</span> Operaciones ilimitadas con Asistente Depeeper IA</li>
                    <li class="flex gap-2"><span class="text-[#d2b8ff]" aria-hidden="true">✓</span> Algoritmo de predicción de demanda y stock-out anticipado</li>
                    <li class="flex gap-2"><span class="text-[#d2b8ff]" aria-hidden="true">✓</span> Soporte Multi-Bodega ilimitado con sincronización en tiempo real</li>
                    <li class="flex gap-2"><span class="text-[#d2b8ff]" aria-hidden="true">✓</span> Exportación automática de órdenes de compra a proveedores</li>
                    <li class="flex gap-2"><span class="text-[#d2b8ff]" aria-hidden="true">✓</span> Soporte técnico prioritario 24/7 y SLA garantizado</li>
                </ul>
                <button type="button" disabled class="mt-4 w-full cursor-not-allowed rounded-xl bg-[#873cf1] px-4 py-3 text-sm font-bold text-[#2b1647] opacity-90" title="La gestión de suscripciones aún no está configurada">
                    Gestión de planes próximamente <span aria-hidden="true" class="ml-1">→</span>
                </button>
            </article>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl bg-[#1d1a2d]">
            <div class="flex items-center justify-between gap-3 border-b border-white/5 px-4 py-3.5 text-sm text-[#e1dbea]">
                <span><span class="mr-2 text-[#d1b4ff]" aria-hidden="true">▤</span> Historial de facturación</span>
                <span aria-hidden="true" class="text-lg text-[#a59bae]">›</span>
            </div>
            <details id="account-settings" class="group border-b border-white/5" @if ($errors->any()) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-sm text-[#e1dbea]">
                    <span><span class="mr-2 text-[#d1b4ff]" aria-hidden="true">⚙</span> Configuración de cuenta</span>
                    <span aria-hidden="true" class="text-lg text-[#a59bae] transition group-open:rotate-90">›</span>
                </summary>
                <div class="border-t border-white/5 px-4 py-4">
                    <h2 class="mb-4 text-sm font-bold text-[#eee9fa]">Datos del perfil</h2>
                    <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <x-form-input
                            id="name"
                            name="name"
                            label="Nombre completo"
                            autocomplete="name"
                            :value="old('name', $user->name)"
                            required
                        />
                        <x-form-input
                            id="email"
                            name="email"
                            label="Correo electrónico"
                            type="email"
                            autocomplete="email"
                            :value="old('email', $user->email)"
                            required
                        />
                        <x-form-input
                            id="company_name"
                            name="company_name"
                            label="Empresa o bodega central"
                            :value="old('company_name', $user->company_name)"
                        />
                        <x-primary-button class="w-full">Guardar cambios</x-primary-button>
                    </form>
                </div>
            </details>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left text-sm font-medium text-[#ffaaa6] hover:bg-white/5">
                    <span><span class="mr-2" aria-hidden="true">⇥</span> Cerrar sesión</span>
                    <span aria-hidden="true" class="text-lg">›</span>
                </button>
            </form>
        </section>
    </main>

    <x-module-nav active="profile" />
@endsection
