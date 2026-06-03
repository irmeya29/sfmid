<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
