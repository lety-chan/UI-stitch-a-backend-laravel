@extends('layouts.app')

@section('title', 'Iniciar sesión | Depeeper')

@section('content')
    <main class="mx-auto flex min-h-screen w-full max-w-[480px] flex-col px-5 pb-10 pt-5 sm:px-8">
        <x-auth-brand
            step="Iniciar sesión"
            :progress="34"
            :action="route('register')"
            action-label="Crear cuenta"
        />

        <section class="mt-8 flex-1">
            <p class="mb-3 inline-flex rounded-full bg-[#29213d] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-[#c9a8ff]">
                Portal de acceso
            </p>
            <h1 class="text-[32px] font-bold leading-tight tracking-tight text-[#f1edff]">Iniciar sesión</h1>
            <p class="mt-2 text-base leading-7 text-[#c3bdd2]">
                Accede a tu panel de control y telemetría de inventario en tiempo real.
            </p>

            <div
                class="mt-7 flex min-h-[60px] items-center justify-center gap-3 rounded-2xl bg-[#1d1a2d] px-4 text-center font-semibold text-[#e4def1]"
                title="La autenticación con Google aún no está configurada"
                aria-disabled="true"
            >
                <span class="bg-gradient-to-br from-blue-400 via-green-400 to-red-400 bg-clip-text text-xl font-black text-transparent">G</span>
                Continuar con Google
                <span class="sr-only">La integración estará disponible próximamente.</span>
            </div>

            <div class="my-6 flex items-center gap-3 text-[12px] font-semibold uppercase tracking-[0.14em] text-[#938ca1]">
                <span class="h-px flex-1 bg-[#342e42]"></span>
                O inicia con tu correo
                <span class="h-px flex-1 bg-[#342e42]"></span>
            </div>

            <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                @csrf

                <x-form-input
                    id="email"
                    name="email"
                    label="Correo electrónico"
                    type="email"
                    autocomplete="email"
                    placeholder="ejemplo@empresa.com"
                    :value="old('email')"
                    required
                />

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="password" class="block text-sm font-semibold text-[#d2ccdc]">Contraseña</label>
                        <span class="text-xs font-semibold text-[#c9a8ff]">Acceso seguro</span>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Ingresa tu contraseña"
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
                </div>

                <label for="remember" class="flex items-center gap-2 text-sm text-[#bbb5c9]">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        value="1"
                        @checked(old('remember'))
                        class="size-4 rounded border-[#4c435e] bg-[#1d1a2d] text-[#873cf1] focus:ring-[#873cf1]"
                    >
                    Mantener sesión iniciada
                </label>

                <x-primary-button class="w-full py-4 text-base">
                    Iniciar sesión <span aria-hidden="true" class="ml-2 text-xl">→</span>
                </x-primary-button>
            </form>

            <p class="mt-8 text-center text-base text-[#c3bdd2]">
                ¿No tienes una cuenta?
                <a href="{{ route('register') }}" class="ml-1 font-bold text-[#d1b4ff] hover:text-white">Regístrate</a>
            </p>
        </section>

        <p class="mt-7 flex items-center justify-center gap-2 rounded-2xl bg-[#0d0a19]/70 px-3 py-3 text-center text-xs font-semibold text-[#bdb5cb]">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-4 shrink-0 text-[#c9a8ff]" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
            </svg>
            Seguridad de nivel bancario y cifrado SSL de 256 bits
        </p>
    </main>
@endsection
