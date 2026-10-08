@props(['name', 'label' => null, 'options' => [], 'value' => null, 'required' => false, 'placeholder' => null, 'hint' => null])
@php
    $key = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $id = $attributes->get('id', 'f_'.str_replace('.', '_', $key));
    $current = (string) old($key, $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes->except('class')->merge(['class' => 'form-select'.($errors->has($key) ? ' border-rose-400' : '')]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @error($key)<p class="form-error">{{ $message }}</p>@enderror
</div>
