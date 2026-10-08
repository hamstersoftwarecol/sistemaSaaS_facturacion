@props(['name', 'label' => null, 'value' => null, 'required' => false, 'rows' => 3, 'hint' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) {{ $attributes->except('class')->merge(['class' => 'form-input'.($errors->has($key) ? ' border-rose-400' : '')]) }}>{{ old($key, $value) }}</textarea>
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @error($key)<p class="form-error">{{ $message }}</p>@enderror
</div>
