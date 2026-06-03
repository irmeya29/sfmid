@props([
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm']) }}>
    @if($title || $subtitle)
        <div class="mb-5">
            @if($title)
                <h3 class="text-base font-black text-slate-950">{{ $title }}</h3>
            @endif
            @if($subtitle)
                <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</section>
