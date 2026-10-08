<x-app-layout title="Sucursales">
    <x-page-header title="Sucursales" subtitle="Puntos de venta, almacenes y oficinas de la empresa">
        @can('branches.create')<a href="{{ route('branches.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nueva sucursal</a>@endcan
    </x-page-header>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($branches as $branch)
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><x-icon name="map-pin" class="h-5 w-5"/></div>
                        <div><p class="font-semibold">{{ $branch->name }}</p><p class="text-xs text-slate-500">{{ $branch->code }}</p></div>
                    </div>
                    <div class="flex gap-1">
                        @if ($branch->is_main)<x-badge color="indigo">Principal</x-badge>@endif
                        @unless ($branch->is_active)<x-badge>Inactiva</x-badge>@endunless
                    </div>
                </div>
                <dl class="mt-4 space-y-1 text-sm text-slate-600">
                    <div>{{ collect([$branch->address, $branch->city])->filter()->implode(', ') ?: 'Sin dirección' }}</div>
                    <div>{{ collect([$branch->phone, $branch->email])->filter()->implode(' · ') }}</div>
                    <div class="text-xs text-slate-400">{{ $branch->users_count }} usuarios asignados</div>
                </dl>
                <div class="mt-4 flex gap-2">
                    @can('branches.edit')<a href="{{ route('branches.edit', $branch) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>@endcan
                    @if (! $branch->is_main)@can('branches.delete')<x-delete-button :action="route('branches.destroy', $branch)" :small="true"/>@endcan @endif
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
