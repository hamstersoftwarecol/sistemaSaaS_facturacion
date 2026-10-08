<x-app-layout title="Clientes">
    <x-page-header title="Clientes" subtitle="Cartera de clientes, saldos y estados de cuenta">
        @can('customers.create')<a href="{{ route('customers.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo cliente</a>@endcan
    </x-page-header>
    <div class="card">
        <form method="GET" class="flex gap-3 border-b border-slate-100 p-4">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, NIT, correo o código…" class="form-input max-w-md">
            <button class="btn-secondary"><x-icon name="search" class="h-4 w-4"/> Buscar</button>
        </form>
        @if ($customers->isEmpty())
            <x-empty-state icon="users" title="Sin clientes" description="Registre sus clientes para empezar a facturar.">
                @can('customers.create')<a href="{{ route('customers.create') }}" class="btn-primary">Crear cliente</a>@endcan
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Cliente</th><th>NIT / ID</th><th>Contacto</th><th>Lista de precios</th><th class="text-right">Saldo</th><th>Estado</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($customers as $customer)
                            @php $balance = $customer->balance(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('customers.show', $customer) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $customer->name }}</a>
                                    @if ($customer->code)<div class="text-xs text-slate-400">{{ $customer->code }}</div>@endif
                                </td>
                                <td>{{ $customer->tax_id ?: '—' }}</td>
                                <td class="text-slate-500"><div>{{ $customer->email }}</div><div class="text-xs">{{ $customer->phone }}</div></td>
                                <td>{{ $customer->priceList?->name ?? 'Precio base' }}</td>
                                <td class="text-right font-medium {{ $balance > 0 ? 'text-amber-600' : 'text-slate-500' }}">{{ money($balance) }}</td>
                                <td><x-badge :color="$customer->is_active ? 'green' : 'gray'">{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</x-badge></td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('customers.ledger', $customer) }}" class="btn-ghost btn-sm" title="Estado de cuenta"><x-icon name="list" class="h-4 w-4"/></a>
                                    @can('customers.edit')<a href="{{ route('customers.edit', $customer) }}" class="btn-ghost btn-sm" title="Editar"><x-icon name="pencil" class="h-4 w-4"/></a>@endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $customers->links() }}</div>
        @endif
    </div>
</x-app-layout>
