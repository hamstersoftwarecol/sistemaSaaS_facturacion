@props(['name', 'label', 'checked' => false, 'hint' => null, 'value' => 1])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
    $isChecked = old('_token') ? (bool) old($key) : (bool) $checked;
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="inline-flex items-start gap-2.5 cursor-pointer">
        <input type="hidden" name="{{ $name }}" value="0">
        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($isChecked) {{ $attributes->except('class')->merge(['class' => 'form-checkbox mt-0.5']) }}>
        <span>
            <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
            @if ($hint)<span class="block text-xs text-slate-500">{{ $hint }}</span>@endif
        </span>
    </label>
    @error($key)<p class="form-error">{{ $message }}</p>@enderror
</div>
