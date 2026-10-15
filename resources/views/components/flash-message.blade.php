@if (session('status'))
    <p role="status" class="mb-4 rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
        {{ session('status') }}
    </p>
@endif
