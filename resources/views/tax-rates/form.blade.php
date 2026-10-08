<x-app-layout :title="$taxRate->exists ? 'Editar impuesto' : 'Nuevo impuesto'">
    <x-page-header :title="$taxRate->exists ? 'Editar '.$taxRate->name : 'Nuevo tipo impositivo'" :back="route('tax-rates.index')"/>
    <form method="POST" action="{{ $taxRate->exists ? route('tax-rates.update', $taxRate) : route('tax-rates.store') }}" class="card card-body grid max-w-2xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        @if ($taxRate->exists) @method('PUT') @endif
        <x-form.input name="name" label="Nombre" :value="$taxRate->name" required placeholder="IVA 19%"/>
        <x-form.input name="rate" type="number" step="0.01" label="Tasa (%)" :value="$taxRate->rate" required/>
        <x-form.checkbox name="is_default" label="Impuesto predeterminado" :checked="$taxRate->is_default"/>
        <x-form.checkbox name="is_active" label="Activo" :checked="$taxRate->is_active"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar</button></div>
    </form>
</x-app-layout>
