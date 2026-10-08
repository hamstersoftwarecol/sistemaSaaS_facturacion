<x-app-layout title="Pérdidas y ganancias">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Ingresos netos" :value="money($data['netRevenue'])" icon="trending-up" color="green"/>
        <x-stat label="Utilidad bruta" :value="money($data['gross'])" icon="chart" color="blue" :hint="'Margen bruto '.num($data['grossMargin'], 1).'%'"/>
        <x-stat label="Gastos operativos" :value="money($data['expensesTotal'])" icon="trending-down" color="red"/>
        <x-stat label="Utilidad neta" :value="money($data['net'])" icon="scale" :color="$data['net'] >= 0 ? 'green' : 'red'" :hint="'Margen neto '.num($data['netMargin'], 1).'%'"/>
    </div>
    <div class="card max-w-3xl">
        <table class="table">
            <tbody class="divide-y divide-slate-100">
                <tr class="bg-slate-50"><td colspan="2" class="font-semibold uppercase text-slate-500">Ingresos</td></tr>
                <tr><td class="pl-8">Ventas (base imponible)</td><td class="text-right">{{ money($data['revenue']) }}</td></tr>
                <tr><td class="pl-8">(−) Devoluciones y notas de crédito</td><td class="text-right text-rose-600">-{{ money($data['returns']) }}</td></tr>
                <tr class="font-semibold"><td>Ingresos netos</td><td class="text-right">{{ money($data['netRevenue']) }}</td></tr>
                <tr><td class="pl-8">(−) Costo de ventas</td><td class="text-right text-rose-600">-{{ money($data['cogs']) }}</td></tr>
                <tr class="bg-indigo-50/50 font-semibold"><td>Utilidad bruta</td><td class="text-right">{{ money($data['gross']) }}</td></tr>
                <tr class="bg-slate-50"><td colspan="2" class="font-semibold uppercase text-slate-500">Otros ingresos</td></tr>
                @forelse ($data['otherIncome'] as $name => $amount)<tr><td class="pl-8">{{ $name }}</td><td class="text-right">{{ money($amount) }}</td></tr>@empty<tr><td class="pl-8 text-slate-400" colspan="2">—</td></tr>@endforelse
                <tr class="bg-slate-50"><td colspan="2" class="font-semibold uppercase text-slate-500">Gastos operativos (sin impuestos)</td></tr>
                @forelse ($data['expenses'] as $name => $amount)<tr><td class="pl-8">{{ $name }}</td><td class="text-right text-rose-600">-{{ money($amount) }}</td></tr>@empty<tr><td class="pl-8 text-slate-400" colspan="2">—</td></tr>@endforelse
            </tbody>
            <tfoot><tr class="text-base"><td>Utilidad neta del periodo</td><td class="text-right {{ $data['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ money($data['net']) }}</td></tr></tfoot>
        </table>
    </div>
</x-app-layout>
