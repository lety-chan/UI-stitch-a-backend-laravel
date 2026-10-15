@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
    'placeholder' => null,
])

<div>
    <label for="{{ $id }}" class="mb-2 block text-sm font-semibold text-[#d2ccdc]">{{ $label }}</label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($value !== null) value="{{ $value }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-white/5 bg-[#1d1a2d] px-4 py-4 text-[15px] text-[#f1edff] outline-none transition placeholder:text-[#898294] focus:border-[#9148ff] focus:ring-2 focus:ring-[#9148ff]/25']) }}
        @error($name)
            aria-invalid="true"
            aria-describedby="{{ $id }}-error"
        @enderror
    >
    @error($name)
        <p id="{{ $id }}-error" class="mt-2 text-sm text-rose-300">{{ $message }}</p>
    @enderror
</div>
