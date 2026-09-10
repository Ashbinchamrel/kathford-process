@props(['eyebrow' => 'Procurement workspace', 'title', 'description', 'actionUrl' => null, 'actionLabel' => null])

<section class="flex flex-col gap-4 rounded-2xl bg-[#0B1E3D] px-5 py-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-7">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-300">{{ $eyebrow }}</p>
        <h2 class="mt-1 text-xl font-bold">{{ $title }}</h2>
        <p class="mt-1 text-sm text-slate-300">{{ $description }}</p>
    </div>
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-400 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            {{ $actionLabel }}
        </a>
    @endif
</section>
