<x-app-layout title="Proveedores">
    <x-page-header title="Proveedores" subtitle="Directorio de proveedores y cuentas por pagar">
        @can('suppliers.create')<a href="{{ route('suppliers.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo proveedor</a>@endcan
    </x-page-header>
    <div class="card">
        <form method="GET" class="flex gap-3 border-b border-slate-100 p-4">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, NIT, contacto o correo…" class="form-input max-w-md">
            <button class="btn-secondary"><x-icon name="search" class="h-4 w-4"/> Buscar</button>
        </form>
        @if ($suppliers->isEmpty())
            <x-empty-state icon="store" title="Sin proveedores" description="Registre a sus proveedores para gestionar compras y pagos."/>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Proveedor</th><th>NIT / ID</th><th>Contacto</th><th class="text-right">Saldo por pagar</th><th>Estado</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($suppliers as $supplier)
                            @php $balance = $supplier->balance(); @endphp
                            <tr>
                                <td><a href="{{ route('suppliers.show', $supplier) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $supplier->name }}</a></td>
                                <td>{{ $supplier->tax_id ?: '—' }}</td>
                                <td class="text-slate-500"><div>{{ $supplier->contact_name }}</div><div class="text-xs">{{ $supplier->email }} {{ $supplier->phone }}</div></td>
                                <td class="text-right font-medium {{ $balance > 0 ? 'text-amber-600' : 'text-slate-500' }}">{{ money($balance) }}</td>
                                <td><x-badge :color="$supplier->is_active ? 'green' : 'gray'">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</x-badge></td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn-ghost btn-sm" title="Estado de cuenta"><x-icon name="list" class="h-4 w-4"/></a>
                                    @can('suppliers.edit')<a href="{{ route('suppliers.edit', $supplier) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a>@endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $suppliers->links() }}</div>
        @endif
    </div>
</x-app-layout>
