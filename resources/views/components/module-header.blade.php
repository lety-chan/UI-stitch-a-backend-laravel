@props(['title', 'eyebrow' => 'DEEPEER AI'])

<header class="sticky top-0 z-20 -mx-4 flex items-center justify-between border-b border-white/5 bg-[#100d20]/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6">
    <a href="{{ route('inventory.index') }}" class="flex min-w-0 items-center gap-2">
        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-[#873cf1] text-lg font-bold text-white shadow-[0_0_18px_rgba(135,60,241,.35)]" aria-hidden="true">✦</span>
        <span class="min-w-0">
            <span class="block text-[9px] font-bold uppercase tracking-[0.18em] text-[#c5a4fb]">{{ $eyebrow }}</span>
            <span class="block truncate text-sm font-bold leading-tight text-[#eee9fa]">{{ $title }}</span>
        </span>
    </a>
    <a href="{{ route('profile.show') }}" aria-label="Perfil de {{ auth()->user()->name }}" class="grid size-9 shrink-0 place-items-center rounded-full border border-[#9d68f5] bg-[#332348] text-xs font-bold text-white">
        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
    </a>
</header>
