@extends('layouts.app')

@section('title', 'Crear cuenta | Depeeper')

@section('content')
    <main class="mx-auto min-h-screen w-full max-w-[480px] px-4 pb-10 pt-5 sm:px-8">
        <x-auth-brand
            step="Creación de cuenta"
            :progress="50"
            :action="route('login')"
            action-label="Iniciar sesión"
        />

        <section class="mt-6 rounded-[28px] bg-[radial-gradient(ellipse_at_top_left,_rgba(127,61,201,0.32),_transparent_65%),#19142a] px-4 pb-5 pt-5 sm:px-5">
            <div class="flex items-center justify-between gap-3">
                <p class="inline-flex items-center gap-2 rounded-full bg-[#332746] px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-[#d8c0ff]">
                    <span aria-hidden="true">☷</span> Paso 1 de 2: credenciales
                </p>
                <span class="shrink-0 text-xs font-semibold text-[#d8c0ff]">50% listo</span>
            </div>
            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-[#3b3449]">
                <span class="block h-full w-1/2 rounded-full bg-[#873cf1]"></span>
            </div>

            <div class="mt-6 flex items-start justify-between gap-3">
                <div>
                    <h1 class="max-w-[310px] text-[30px] font-bold leading-tight tracking-tight text-[#f1edff]">Crea tu cuenta en Depeeper</h1>
                    <p class="mt-2 text-[15px] leading-6 text-[#c3bdd2]">
                        Potencia tu logística con predicción algorítmica y telemetría predictiva de inventario en tiempo real.
                    </p>
                </div>
                <span class="mt-1 size-2 shrink-0 rounded-full bg-[#d2b8ff]"></span>
            </div>

            <div class="mt-4 flex items-center gap-3 rounded-2xl bg-[#29243a] p-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#4a2c78] text-lg text-[#d8c0ff]" aria-hidden="true">✺</span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-[#e4def1]">Cluster IA activo</p>
                    <p class="text-sm leading-5 text-[#bcb5cb]">Optimización predictiva de stock</p>
                </div>
                <span class="rounded-full bg-[#3a344c] px-3 py-1 text-[11px] font-semibold leading-4 text-[#d7c6f6]">99.98% latencia 12ms</span>
            </div>
        </section>

        <div class="mt-6 space-y-3">
            <div class="flex min-h-14 items-center justify-center gap-3 rounded-2xl bg-[#1d1a2d] px-4 text-sm font-semibold text-[#d9d3e4]" title="El registro con Google Workspace aún no está configurado" aria-disabled="true">
                <span class="bg-gradient-to-br from-blue-400 via-green-400 to-red-400 bg-clip-text text-xl font-black text-transparent">G</span>
                Continuar con Google Workspace
                <span class="rounded-md bg-[#332d43] px-2 py-1 text-[10px] text-[#c6b6df]">Empresarial</span>
            </div>
            <div class="flex min-h-14 items-center justify-center gap-3 rounded-2xl bg-[#1d1a2d] px-4 text-sm font-semibold text-[#d9d3e4]" title="El registro con Microsoft 365 aún no está configurado" aria-disabled="true">
                <span class="grid size-5 grid-cols-2 gap-0.5" aria-hidden="true">
                    <span class="bg-orange-400"></span><span class="bg-green-400"></span>
                    <span class="bg-blue-400"></span><span class="bg-yellow-400"></span>
                </span>
                Continuar con Microsoft 365
            </div>
        </div>

        <div class="my-5 flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.1em] text-[#bcb5cb]">
            <span class="h-px flex-1 bg-[#393348]"></span>
            O con correo corporativo
            <span class="h-px flex-1 bg-[#393348]"></span>
        </div>

        <form action="{{ route('register.store') }}" method="POST" class="space-y-4">
            @csrf

            <x-form-input
                id="name"
                name="name"
                label="Nombre completo *"
                autocomplete="name"
                placeholder="Ej. Carlos Mendoza"
                :value="old('name')"
                required
            />

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label for="email" class="block text-sm font-semibold text-[#d2ccdc]">Correo corporativo *</label>
                    <span class="text-xs font-medium text-[#d1b4ff]">Dominio laboral</span>
                </div>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    placeholder="nombre@empresa.com"
                    value="{{ old('email') }}"
                    required
                    @error('email')
                        aria-invalid="true"
                        aria-describedby="email-error"
                    @enderror
                    class="w-full rounded-2xl border border-white/5 bg-[#1d1a2d] px-4 py-4 text-[15px] text-[#f1edff] outline-none transition placeholder:text-[#898294] focus:border-[#9148ff] focus:ring-2 focus:ring-[#9148ff]/25"
                >
                @error('email')
                    <p id="email-error" class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <x-form-input
                id="company_name"
                name="company_name"
                label="Nombre de la empresa o bodega central *"
                placeholder="Ej. Logística Global Tech"
                :value="old('company_name')"
                required
            />

            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <label for="password" class="block text-sm font-semibold text-[#d2ccdc]">Contraseña maestra *</label>
                    <span class="text-xs font-medium text-[#c9a8ff]">Mínimo 8 caracteres</span>
                </div>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Usa mayúsculas y al menos un símbolo"
                    required
                    @error('password')
                        aria-invalid="true"
                        aria-describedby="password-error"
                    @enderror
                    class="w-full rounded-2xl border border-white/5 bg-[#1d1a2d] px-4 py-4 text-[15px] text-[#f1edff] outline-none transition placeholder:text-[#898294] focus:border-[#9148ff] focus:ring-2 focus:ring-[#9148ff]/25"
                >
                @error('password')
                    <p id="password-error" class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
                <div class="mt-2 flex flex-wrap gap-2 text-[11px] font-semibold text-[#aaa2b6]">
                    <span class="rounded-full bg-[#29243a] px-2.5 py-1">✓ 8+ caracteres</span>
                    <span class="rounded-full bg-[#29243a] px-2.5 py-1">✓ Mayúscula</span>
                    <span class="rounded-full bg-[#29243a] px-2.5 py-1">✓ Símbolo</span>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[#d2ccdc]">Confirmar contraseña *</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Repite tu contraseña"
                    required
                    class="w-full rounded-2xl border border-white/5 bg-[#1d1a2d] px-4 py-4 text-[15px] text-[#f1edff] outline-none transition placeholder:text-[#898294] focus:border-[#9148ff] focus:ring-2 focus:ring-[#9148ff]/25"
                >
            </div>

            @error('password_confirmation')
                <p class="text-sm text-rose-300">{{ $message }}</p>
            @enderror

            <aside class="flex gap-3 rounded-2xl bg-[#1d1a2d] p-4">
                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-[#873cf1] text-lg text-white" aria-hidden="true">✺</span>
                <div>
                    <h2 class="text-sm font-bold text-[#e8e1f4]">Asistente Depeeper Predicción</h2>
                    <p class="mt-1 text-sm leading-5 text-[#beb7ca]">
                        Al activar tu cuenta, podrás conectar tus bodegas al motor de demanda estacional.
                    </p>
                </div>
            </aside>

            <div>
                <label for="terms" class="flex items-start gap-3 text-sm leading-5 text-[#cbc4d4]">
                    <input
                        id="terms"
                        name="terms"
                        type="checkbox"
                        value="1"
                        @checked(old('terms'))
                        required
                        class="mt-0.5 size-5 shrink-0 rounded border-[#5e4c77] bg-[#1d1a2d] text-[#873cf1] focus:ring-[#873cf1]"
                    >
                    <span>Acepto los <span class="font-semibold text-[#e1d1ff] underline">Términos de Servicio</span> y la <span class="font-semibold text-[#e1d1ff] underline">Política de Privacidad</span> de Depeeper IA y telemetría de stock.</span>
                </label>
                @error('terms')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <x-primary-button class="w-full py-4 text-base">
                Crear cuenta y continuar <span aria-hidden="true" class="ml-2 text-xl">→</span>
            </x-primary-button>
        </form>

        <p class="mt-7 text-center text-sm text-[#c3bdd2]">
            ¿Ya tienes una cuenta registrada?
            <a href="{{ route('login') }}" class="ml-1 font-bold text-[#d1b4ff] hover:text-white">Iniciar sesión</a>
        </p>

        <p class="mt-6 flex items-center justify-center gap-2 rounded-2xl bg-[#0d0a19]/70 px-3 py-3 text-center text-xs text-[#aaa2b6]">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="4" y="10" width="16" height="11" rx="2" />
                <path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3" />
            </svg>
            Cifrado SSL de 256 bits y cumplimiento de datos industriales
        </p>
    </main>
@endsection
