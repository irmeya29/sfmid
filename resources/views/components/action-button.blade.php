@props([
    'href' => null,
    'type' => 'button',
    'icon' => 'eye',
    'label',
    'tone' => 'neutral',
])

@php
    $classes = [
        'neutral' => 'border-[#2676B3]/20 bg-white text-[#2676B3] hover:border-[#2676B3]/40 hover:bg-[#EAF4FB]',
        'primary' => 'border-[#2676B3] bg-[#2676B3] text-white hover:bg-[#1E5F91]',
        'success' => 'border-[#2676B3]/25 bg-[#EAF4FB] text-[#2676B3] hover:bg-[#DCEEF8]',
        'warning' => 'border-[#FA820A]/25 bg-[#FFF3E7] text-[#D96B00] hover:bg-[#FFE3C2]',
        'danger' => 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100',
        'info' => 'border-[#2676B3]/25 bg-[#EAF4FB] text-[#2676B3] hover:bg-[#DCEEF8]',
    ][$tone] ?? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50';
@endphp

@if($href)
    <a href="{{ $href }}" data-tooltip="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => "inline-flex h-9 w-9 items-center justify-center rounded-xl border transition {$classes}"]) }}>
        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
    </a>
@else
    <button type="{{ $type }}" data-tooltip="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => "inline-flex h-9 w-9 items-center justify-center rounded-xl border transition {$classes}"]) }}>
        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
    </button>
@endif
