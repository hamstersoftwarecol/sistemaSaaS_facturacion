<x-app-layout :title="$branch->exists ? 'Editar sucursal' : 'Nueva sucursal'">
    <x-page-header :title="$branch->exists ? 'Editar '.$branch->name : 'Nueva sucursal'" :back="route('branches.index')"/>
    <form method="POST" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        @if ($branch->exists) @method('PUT') @endif
        <x-form.input name="name" label="Nombre" :value="$branch->name" required/>
        <x-form.input name="code" label="Código" :value="$branch->code" placeholder="NOR, SUR…"/>
        <x-form.input name="email" type="email" label="Correo" :value="$branch->email"/>
        <x-form.input name="phone" label="Teléfono" :value="$branch->phone"/>
        <x-form.input name="address" label="Dirección" :value="$branch->address"/>
        <x-form.input name="city" label="Ciudad" :value="$branch->city"/>
        <x-form.checkbox name="is_main" label="Sucursal principal" :checked="$branch->is_main" hint="Se usa por defecto cuando no hay sucursal seleccionada."/>
        <x-form.checkbox name="is_active" label="Activa" :checked="$branch->is_active"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar sucursal</button></div>
    </form>
</x-app-layout>
