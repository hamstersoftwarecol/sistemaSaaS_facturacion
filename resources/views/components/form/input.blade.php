@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'prefix' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
    $current = $type === 'password' ? null : old($key, $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
    @endif
    <div class="relative">
        @if ($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">{{ $prefix }}</span>
        @endif
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}" @required($required)
            {{ $attributes->except('class')->merge(['class' => 'form-input'.($prefix ? ' pl-8' : '').($errors->has($key) ? ' border-rose-400' : '')]) }}>
    </div>
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @error($key)<p class="form-error">{{ $message }}</p>@enderror
</div>
