@props([
    'title' => 'Aucune donnée',
    'message' => 'Les éléments apparaîtront ici dès qu’ils seront enregistrés.',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center']) }}>
    <p class="text-base font-black text-slate-800">{{ $title }}</p>
    <p class="mt-2 text-sm text-slate-500">{{ $message }}</p>
    @if(trim($slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
