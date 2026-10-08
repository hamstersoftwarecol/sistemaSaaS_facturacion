<x-app-layout title="Ventas por cliente">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat label="Facturado en el periodo" :value="money($data['total'])" icon="document"/>
        <x-stat label="Saldo total de clientes" :value="money($data['balance'])" icon="clock" color="yellow"/>
    </div>
    <div class="card overflow-x-auto">
        <table class="table"><thead><tr><th>Cliente</th><th>NIT</th><th class="text-center">Facturas</th><th class="text-right">Facturado</th><th class="text-right">Saldo actual</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($data['rows'] as $row)
                    <tr><td>{{ $row['customer']->name }}</td><td class="text-slate-500">{{ $row['customer']->tax_id }}</td><td class="text-center">{{ $row['count'] }}</td><td class="text-right font-medium">{{ money($row['total']) }}</td><td class="text-right">{{ money($row['balance']) }}</td><td class="text-right"><a href="{{ route('customers.ledger', $row['customer']) }}" class="link text-xs">Estado de cuenta</a></td></tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-500">Sin datos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
