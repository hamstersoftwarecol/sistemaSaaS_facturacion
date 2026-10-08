<x-app-layout title="Roles y permisos">
    <x-page-header title="Roles y permisos" subtitle="Defina qué puede ver y hacer cada perfil de usuario">
        @can('roles.create')<a href="{{ route('roles.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo rol</a>@endcan
    </x-page-header>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($roles as $role)
            @php $count = count($role->permissions ?? []); @endphp
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div><p class="font-semibold text-slate-900">{{ $role->name }}</p><p class="text-sm text-slate-500">{{ $role->description }}</p></div>
                    @if ($role->is_system)<x-badge color="purple">Sistema</x-badge>@endif
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-slate-500"><span>{{ $count }} de {{ $total }} permisos</span><span>{{ $role->users_count }} usuarios</span></div>
                    <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-indigo-500" style="width: {{ $total ? round($count / $total * 100) : 0 }}%"></div></div>
                </div>
                <div class="mt-4 flex gap-2">
                    @can('roles.edit')<a href="{{ route('roles.edit', $role) }}" class="btn-secondary btn-sm"><x-icon name="shield" class="h-4 w-4"/> Permisos</a>@endcan
                    @if (! $role->is_system && ! $role->users_count)@can('roles.delete')<x-delete-button :action="route('roles.destroy', $role)" :small="true"/>@endcan @endif
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
