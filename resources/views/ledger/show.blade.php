@php $isCustomer = $partyType === 'customer'; $route = $isCustomer ? 'customers' : 'suppliers'; @endphp
<x-app-layout :title="'Estado de cuenta · '.$party->name">
    <x-page-header :title="'Libro mayor · '.$party->name" :subtitle="$isCustomer ? 'Movimientos de cuentas por cobrar del cliente' : 'Movimientos de cuentas por pagar con el proveedor'" :back="route($route.'.show', $party)">
        <button onclick="window.print()" class="btn-secondary no-print"><x-icon name="printer" class="h-4 w-4"/> Imprimir</button>
    </x-page-header>

    <form method="GET" class="card no-print mb-5 flex flex-wrap items-end gap-3 p-4">
        <div><label class="form-label">Desde</label><input type="date" name="from" value="{{ request('from') }}" class="form-input"></div>
        <div><label class="form-label">Hasta</label><input type="date" name="to" value="{{ request('to') }}" class="form-input"></div>
        <button class="btn-secondary">Aplicar</button>
    </form>

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Saldo inicial" :value="money($ledger['opening'])"/>
        <x-stat :label="$isCustomer ? 'Cargos (facturas)' : 'Cargos (pagos/devol.)'" :value="money($ledger['debit'])"/>
        <x-stat :label="$isCustomer ? 'Abonos (cobros/NC)' : 'Abonos (compras)'" :value="money($ledger['credit'])"/>
        <x-stat :label="$isCustomer ? 'Saldo a cobrar' : 'Saldo a pagar'" :value="money($ledger['closing'])" :color="$ledger['closing'] > 0 ? 'yellow' : 'green'" icon="scale"/>
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Número</th><th>Detalle</th><th class="text-right">Debe</th><th class="text-right">Haber</th><th class="text-right">Saldo</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <tr class="bg-slate-50/60"><td colspan="6" class="font-medium text-slate-500">Saldo anterior</td><td class="text-right font-semibold">{{ money($ledger['opening']) }}</td></tr>
                @forelse ($ledger['rows'] as $row)
                    <tr>
                        <td class="whitespace-nowrap">{{ fdate($row['date']) }}</td>
                        <td>{{ $row['type'] }}</td>
                        <td><a href="{{ $row['url'] }}" class="link">{{ $row['number'] }}</a></td>
                        <td class="text-slate-500">{{ $row['description'] }}</td>
                        <td class="text-right">{{ $row['debit'] ? money($row['debit']) : '' }}</td>
                        <td class="text-right">{{ $row['credit'] ? money($row['credit']) : '' }}</td>
                        <td class="text-right font-medium">{{ money($row['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-slate-500">Sin movimientos en el periodo.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr><td colspan="4">Totales del periodo</td><td class="text-right">{{ money($ledger['debit']) }}</td><td class="text-right">{{ money($ledger['credit']) }}</td><td class="text-right">{{ money($ledger['closing']) }}</td></tr>
            </tfoot>
        </table>
    </div>
</x-app-layout>
