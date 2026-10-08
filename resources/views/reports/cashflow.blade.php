<x-app-layout title="Flujo de caja">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Saldo inicial" :value="money($data['opening'])" icon="banknotes"/>
        <x-stat label="Entradas" :value="money($data['in'])" icon="trending-up" color="green"/>
        <x-stat label="Salidas" :value="money($data['out'])" icon="trending-down" color="red"/>
        <x-stat label="Saldo final" :value="money($data['closing'])" icon="scale" color="blue"/>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card overflow-x-auto">
            <div class="card-header"><h2 class="card-title">Por cuenta</h2></div>
            <table class="table"><thead><tr><th>Cuenta</th><th class="text-right">Inicial</th><th class="text-right">Entradas</th><th class="text-right">Salidas</th><th class="text-right">Final</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@foreach ($data['accounts'] as $row)<tr><td><a class="link" href="{{ route('accounts.show', $row['account']) }}">{{ $row['account']->name }}</a></td><td class="text-right">{{ money($row['opening']) }}</td><td class="text-right text-emerald-600">{{ money($row['in']) }}</td><td class="text-right text-rose-600">{{ money($row['out']) }}</td><td class="text-right font-semibold">{{ money($row['closing']) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <div class="card overflow-x-auto">
            <div class="card-header"><h2 class="card-title">Por tipo de movimiento</h2></div>
            <table class="table"><thead><tr><th>Concepto</th><th class="text-right">Entradas</th><th class="text-right">Salidas</th><th class="text-right">Neto</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@forelse ($data['byType'] as $row)<tr><td>{{ \App\Models\AccountTransaction::TYPES[$row->type] ?? $row->type }}</td><td class="text-right text-emerald-600">{{ money($row->inflow) }}</td><td class="text-right text-rose-600">{{ money($row->outflow) }}</td><td class="text-right font-medium">{{ money($row->inflow - $row->outflow) }}</td></tr>@empty<tr><td colspan="4" class="py-6 text-center text-slate-500">Sin movimientos.</td></tr>@endforelse</tbody>
            </table>
        </div>
    </div>
</x-app-layout>
