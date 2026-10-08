<x-app-layout title="Cuentas por pagar">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3">
        <x-stat label="Al día" :value="money($data['totals']['current'])" color="green"/>
        <x-stat label="1-30 días" :value="money($data['totals']['d30'])" color="yellow"/>
        <x-stat label="31-60 días" :value="money($data['totals']['d60'])" color="yellow"/>
        <x-stat label="61-90 días" :value="money($data['totals']['d90'])" color="red"/>
        <x-stat label="+90 días" :value="money($data['totals']['over90'])" color="red"/>
        <x-stat label="Total" :value="money($data['totals']['total'])" icon="scale"/>
    </div>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Proveedor</th><th class="text-center">Docs.</th><th class="text-right">Al día</th><th class="text-right">1-30</th><th class="text-right">31-60</th><th class="text-right">61-90</th><th class="text-right">+90</th><th class="text-right">Total</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($data['rows'] as $row)
                    <tr>
                        <td>@if ($row['party'])<a class="link" href="{{ route('suppliers.ledger', $row['party']) }}">{{ $row['party']->name }}</a>@else — @endif</td>
                        <td class="text-center">{{ $row['count'] }}</td>
                        <td class="text-right">{{ money($row['current']) }}</td>
                        <td class="text-right">{{ money($row['d30']) }}</td>
                        <td class="text-right">{{ money($row['d60']) }}</td>
                        <td class="text-right {{ $row['d90'] > 0 ? 'text-rose-600' : '' }}">{{ money($row['d90']) }}</td>
                        <td class="text-right {{ $row['over90'] > 0 ? 'text-rose-600 font-semibold' : '' }}">{{ money($row['over90']) }}</td>
                        <td class="text-right font-semibold">{{ money($row['total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-slate-500">No hay saldos pendientes.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><td colspan="2">Totales</td>@foreach (['current', 'd30', 'd60', 'd90', 'over90', 'total'] as $k)<td class="text-right">{{ money($data['totals'][$k]) }}</td>@endforeach</tr></tfoot>
        </table>
    </div>
</x-app-layout>
