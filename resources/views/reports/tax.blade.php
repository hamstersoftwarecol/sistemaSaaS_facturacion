<x-app-layout title="Informe de impuestos">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Impuesto repercutido (ventas)" :value="money($data['salesTax'])" icon="trending-up" color="green"/>
        <x-stat label="Impuesto soportado (compras y gastos)" :value="money($data['purchasesTax'])" icon="trending-down" color="red" :hint="'De gastos: '.money($data['expenseTax'])"/>
        <x-stat :label="$data['net'] >= 0 ? 'Saldo a pagar' : 'Saldo a favor'" :value="money(abs($data['net']))" icon="scale" :color="$data['net'] >= 0 ? 'yellow' : 'blue'"/>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @foreach (['sales' => 'Ventas (facturas − notas de crédito)', 'purchases' => 'Compras (órdenes recibidas − notas de débito)'] as $key => $title)
            <div class="card overflow-x-auto">
                <div class="card-header"><h2 class="card-title">{{ $title }}</h2></div>
                <table class="table"><thead><tr><th>Tasa</th><th class="text-right">Base imponible</th><th class="text-right">Impuesto</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">@forelse ($data[$key] as $row)<tr><td>{{ pct($row['rate']) }}</td><td class="text-right">{{ money($row['base']) }}</td><td class="text-right font-medium">{{ money($row['tax']) }}</td></tr>@empty<tr><td colspan="3" class="py-6 text-center text-slate-500">Sin operaciones.</td></tr>@endforelse</tbody>
                    <tfoot><tr><td>Total</td><td class="text-right">{{ money($data[$key]->sum('base')) }}</td><td class="text-right">{{ money($data[$key]->sum('tax')) }}</td></tr></tfoot>
                </table>
            </div>
        @endforeach
    </div>
</x-app-layout>
