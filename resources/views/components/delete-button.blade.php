@props(['action', 'label' => 'Eliminar', 'confirm' => '¿Seguro que desea eliminar este registro? Esta acción no se puede deshacer.', 'icon' => true, 'small' => false])
<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($confirm))">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => $small ? 'btn-ghost btn-sm text-rose-600 hover:bg-rose-50' : 'btn-secondary text-rose-600']) }} title="{{ $label }}">
        @if ($icon)<x-icon name="trash" class="h-4 w-4"/>@endif
        @if (! $small || ! $icon)<span>{{ $label }}</span>@endif
    </button>
</form>
