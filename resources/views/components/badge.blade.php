@props(['color' => 'gray'])
<span {{ $attributes->merge(['class' => 'badge '.badge_classes($color)]) }}>{{ $slot }}</span>
