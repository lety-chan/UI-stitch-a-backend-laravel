@props(['active' => ''])

<nav aria-label="Navegación principal" class="fixed inset-x-0 bottom-3 z-30 mx-auto flex w-[calc(100%-1.5rem)] max-w-[448px] items-center justify-around rounded-full border border-white/5 bg-[#28233a]/95 px-1.5 py-2 shadow-xl shadow-black/40 backdrop-blur">
    @foreach ([
        ['id' => 'inventory', 'route' => 'inventory.index', 'label' => 'Inventario', 'icon' => '▣'],
        ['id' => 'analytics', 'route' => 'analytics.index', 'label' => 'Analítica', 'icon' => '▥'],
        ['id' => 'assistant', 'route' => 'assistant.index', 'label' => 'IA', 'icon' => '✺'],
        ['id' => 'restock', 'route' => 'restock.index', 'label' => 'Reabastecer', 'icon' => '⇄'],
        ['id' => 'profile', 'route' => 'profile.show', 'label' => 'Perfil', 'icon' => '◉'],
    ] as $item)
        <a
            href="{{ route($item['route']) }}"
            @if ($active === $item['id']) aria-current="page" @endif
            @class([
                'flex min-w-0 flex-col items-center justify-center gap-0.5 rounded-full px-2 py-1 text-[9px] leading-tight sm:text-[10px]',
                'text-[#c8c0d2]' => $active !== $item['id'] && $item['id'] !== 'assistant',
                'font-bold text-[#eee9fa]' => $active === $item['id'],
                'size-12 bg-[#873cf1] px-0 py-0 text-xl text-white shadow-[0_0_22px_rgba(135,60,241,.5)]' => $item['id'] === 'assistant' && $active === 'assistant',
                'size-11 bg-[#352447] px-0 py-0 text-lg text-[#d5b7ff]' => $item['id'] === 'assistant' && $active !== 'assistant',
            ])
        >
            <span aria-hidden="true" @class(['text-base' => $item['id'] !== 'assistant', 'text-xl' => $item['id'] === 'assistant'])>{{ $item['icon'] }}</span>
            @if ($item['id'] !== 'assistant')
                <span>{{ $item['label'] }}</span>
            @else
                <span class="sr-only">Asistente IA</span>
            @endif
        </a>
    @endforeach
</nav>
