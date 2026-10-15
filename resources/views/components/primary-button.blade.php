<button
    type="submit"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-2xl bg-[#873cf1] px-5 py-3 text-sm font-bold text-white shadow-[0_8px_28px_rgba(135,60,241,0.3)] transition hover:bg-[#974fff] focus:outline-none focus:ring-2 focus:ring-[#c9a8ff] focus:ring-offset-2 focus:ring-offset-[#100d20]']) }}
>
    {{ $slot }}
</button>
