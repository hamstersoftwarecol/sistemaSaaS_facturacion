<x-app-layout :title="$user->exists ? 'Editar usuario' : 'Nuevo usuario'">
    <x-page-header :title="$user->exists ? 'Editar '.$user->name : 'Nuevo usuario'" :back="route('users.index')"/>
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <x-form.input name="name" label="Nombre completo" :value="$user->name" required/>
        <x-form.input name="email" type="email" label="Correo electrónico" :value="$user->email" required/>
        <x-form.input name="phone" label="Teléfono" :value="$user->phone"/>
        @if ($user->is_owner)
            <div><p class="form-label">Rol</p><p class="rounded-lg bg-violet-50 px-3 py-2 text-sm text-violet-700">Propietario · acceso total</p></div>
        @else
            <x-form.select name="role_id" label="Rol" :options="$roles->pluck('name', 'id')->all()" :value="$user->role_id" placeholder="Seleccione…" required/>
        @endif
        <x-form.select name="branch_id" label="Sucursal asignada" :options="$branches->pluck('name', 'id')->all()" :value="$user->branch_id" placeholder="Todas las sucursales" hint="Si asigna una sucursal, el usuario solo verá y operará en ella."/>
        <div></div>
        <x-form.input name="password" type="password" :label="$user->exists ? 'Nueva contraseña (opcional)' : 'Contraseña'" :required="! $user->exists" autocomplete="new-password"/>
        <x-form.input name="password_confirmation" type="password" label="Confirmar contraseña" :required="! $user->exists" autocomplete="new-password"/>
        @unless ($user->is_owner)<x-form.checkbox name="is_active" label="Usuario activo" :checked="$user->is_active"/>@endunless
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar usuario</button></div>
    </form>
</x-app-layout>
