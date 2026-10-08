<x-admin-layout title="Empresas">
    <x-page-header title="Empresas" subtitle="Clientes de la plataforma (tenants)">
        <a href="{{ route('admin.companies.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nueva empresa</a>
    </x-page-header>
    <div class="card">
        <form method="GET" class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <input type="search" name="q" value="{{ request('q') }}" class="form-input max-w-sm" placeholder="Nombre, correo o NIT">
            <select name="status" class="form-select w-40"><option value="">Todos</option><option value="active" @selected(request('status') === 'active')>Activas</option><option value="suspended" @selected(request('status') === 'suspended')>Suspendidas</option></select>
            <button class="btn-secondary">Filtrar</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Empresa</th><th>Propietario</th><th>Plan</th><th>Suscripción</th><th class="text-center">Usuarios</th><th>Alta</th><th>Estado</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($companies as $c)
                        <tr>
                            <td><a href="{{ route('admin.companies.show', $c) }}" class="font-medium hover:text-indigo-600">{{ $c->name }}</a><div class="text-xs text-slate-400">{{ $c->tax_id }}</div></td>
                            <td class="text-slate-500">{{ $c->owner?->name }}<div class="text-xs">{{ $c->owner?->email }}</div></td>
                            <td>{{ $c->subscription?->plan?->name ?? '—' }}</td>
                            <td>@if ($c->subscription)<x-badge :color="$c->subscription->statusColor()">{{ $c->subscription->statusLabel() }}</x-badge><div class="text-xs text-slate-400">{{ $c->subscription->expiresAt() ? 'hasta '.fdate($c->subscription->expiresAt()) : '' }}</div>@endif</td>
                            <td class="text-center">{{ $c->users_count }}</td>
                            <td class="text-slate-500">{{ fdate($c->created_at) }}</td>
                            <td><x-badge :color="$c->status === 'active' ? 'green' : 'red'">{{ $c->status === 'active' ? 'Activa' : 'Suspendida' }}</x-badge></td>
                            <td class="text-right"><a href="{{ route('admin.companies.edit', $c) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $companies->links() }}</div>
    </div>
</x-admin-layout>
