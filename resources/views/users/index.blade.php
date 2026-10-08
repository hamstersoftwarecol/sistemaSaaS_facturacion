<x-app-layout title="Usuarios">
    <x-page-header title="Usuarios" subtitle="Personas con acceso al sistema de su empresa">
        @can('users.create')<a href="{{ route('users.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo usuario</a>@endcan
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Usuario</th><th>Rol</th><th>Sucursal</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">{{ $user->initials() }}</span>
                                <div><p class="font-medium text-slate-900">{{ $user->name }}</p><p class="text-xs text-slate-500">{{ $user->email }}</p></div>
                            </div>
                        </td>
                        <td><x-badge :color="$user->is_owner ? 'purple' : 'indigo'">{{ $user->roleName() }}</x-badge></td>
                        <td>{{ $user->branch?->name ?? 'Todas' }}</td>
                        <td class="text-slate-500">{{ $user->last_login_at?->diffForHumans() ?? 'Nunca' }}</td>
                        <td><x-badge :color="$user->is_active ? 'green' : 'gray'">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</x-badge></td>
                        <td class="text-right whitespace-nowrap">
                            @can('users.edit')@if (! $user->is_owner || auth()->user()->is_owner)<a href="{{ route('users.edit', $user) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a>@endif @endcan
                            @if (! $user->is_owner && $user->id !== auth()->id())@can('users.delete')<x-delete-button :action="route('users.destroy', $user)" :small="true"/>@endcan @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
