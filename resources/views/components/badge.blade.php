@props([
    'tone' => 'slate',
])

@php
    $classes = [
        'slate' => 'bg-slate-100 text-slate-700',
        'green' => 'bg-[#EAF4FB] text-[#2676B3]',
        'red' => 'bg-red-100 text-red-800',
        'yellow' => 'bg-[#FFF3E7] text-[#D96B00]',
        'blue' => 'bg-[#EAF4FB] text-[#2676B3]',
        'purple' => 'bg-[#EAF4FB] text-[#2676B3]',
    ][$tone] ?? 'bg-slate-100 text-slate-700';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-3 py-1 text-xs font-black {$classes}"]) }}>
    {{ $slot }}
</span>
