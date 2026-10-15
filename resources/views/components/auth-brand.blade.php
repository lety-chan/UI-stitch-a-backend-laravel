@props([
    'step',
    'progress' => 34,
    'action',
    'actionLabel',
])

<header>
    <div class="relative flex h-12 items-center justify-between">
        <a href="{{ route('welcome') }}" aria-label="Volver al inicio" class="inline-flex size-10 items-center justify-start text-3xl leading-none text-[#ddd5ea]">
            <span aria-hidden="true">←</span>
        </a>

        <a href="{{ route('welcome') }}" class="absolute left-1/2 inline-flex -translate-x-1/2 items-center gap-2 text-[21px] font-bold tracking-tight text-[#eee9fa]">
            <span class="grid size-8 place-items-center rounded-xl bg-[#873cf1] text-white shadow-[0_0_20px_rgba(135,60,241,0.35)]" aria-hidden="true">✦</span>
            Depeeper
        </a>

        <a href="{{ $action }}" aria-label="{{ $actionLabel }}" class="grid size-10 place-items-center rounded-full bg-[#d4b9ff] text-[#271344]">
            <svg aria-hidden="true" viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="8" r="3.5" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 20c.5-3.5 2.8-5.5 7-5.5s6.5 2 7 5.5" />
            </svg>
        </a>
    </div>

    <div class="mt-4 flex items-center justify-between gap-4">
        <p class="text-sm font-medium text-[#c9c2d2]">{{ $step }}</p>
        <div class="flex items-center gap-1.5" aria-hidden="true">
            <span class="h-1.5 w-7 rounded-full bg-[#c9a8ff] shadow-[0_0_12px_rgba(201,168,255,0.45)]"></span>
            <span class="h-1.5 w-2 rounded-full bg-[#393348]"></span>
            <span class="h-1.5 w-2 rounded-full bg-[#393348]"></span>
        </div>
    </div>
    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[#312a40]">
        <span class="block h-full rounded-full bg-[#873cf1]" style="width: {{ $progress }}%"></span>
    </div>
</header>
