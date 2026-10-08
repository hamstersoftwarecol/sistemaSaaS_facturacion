@props(['label', 'value', 'icon' => null, 'color' => 'indigo', 'hint' => null])
@php
    $tones = [
        'indigo' => 'bg-indigo-50 text-indigo-600', 'green' => 'bg-emerald-50 text-emerald-600', 'red' => 'bg-rose-50 text-rose-600',
        'yellow' => 'bg-amber-50 text-amber-600', 'blue' => 'bg-sky-50 text-sky-600', 'purple' => 'bg-violet-50 text-violet-600', 'gray' => 'bg-slate-100 text-slate-600',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'stat']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="stat-label">{{ $label }}</p>
            <p class="stat-value truncate">{{ $value }}</p>
        </div>
        @if ($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $tones[$color] ?? $tones['indigo'] }}">
                <x-icon :name="$icon" class="h-5 w-5"/>
            </div>
        @endif
    </div>
    @if ($hint)
        <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
