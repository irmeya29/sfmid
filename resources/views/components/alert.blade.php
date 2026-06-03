@props([
    'type' => 'success',
    'message' => null,
])

@if($message)
    @php
        $classes = [
            'success' => 'border-[#2676B3]/25 bg-[#EAF4FB] text-[#1E5F91]',
            'error' => 'border-red-200 bg-red-50 text-red-900',
            'warning' => 'border-[#FA820A]/25 bg-[#FFF3E7] text-[#D96B00]',
            'info' => 'border-[#2676B3]/25 bg-[#EAF4FB] text-[#1E5F91]',
        ][$type] ?? 'border-slate-200 bg-white text-slate-800';
    @endphp

    <div {{ $attributes->merge(['class' => "mb-5 rounded-2xl border px-4 py-3 text-sm font-semibold shadow-sm {$classes}"]) }}>
        {{ $message }}
    </div>
@endif
