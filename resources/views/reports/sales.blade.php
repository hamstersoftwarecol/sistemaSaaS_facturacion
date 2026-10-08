<x-app-layout title="Informe de ventas">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Facturado (con impuestos)" :value="money($data['total'])" icon="document" :hint="$data['count'].' facturas'"/>
        <x-stat label="Base imponible" :value="money($data['subtotal'])" icon="calculator" color="blue" :hint="'Impuestos: '.money($data['tax'])"/>
        <x-stat label="Cobrado" :value="money($data['collected'])" icon="banknotes" color="green"/>
        <x-stat label="Pendiente de cobro" :value="money($data['pending'])" icon="clock" color="yellow" :hint="'Notas de crédito: '.money($data['credits'])"/>
    </div>
    @if ($data['byMonth']->count() > 1)
        <div class="card card-body mb-6"><canvas id="salesChart" height="80"></canvas></div>
        @push('scripts')<script>document.addEventListener('DOMContentLoaded', () => new Chart(document.getElementById('salesChart'), { type: 'bar', data: { labels: @js($data['byMonth']->keys()), datasets: [{ label: 'Ventas', data: @js($data['byMonth']->values()), backgroundColor: '#6366f1', borderRadius: 6 }] }, options: { plugins: { legend: { display: false } } } }));</script>@endpush
    @endif
    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card overflow-x-auto">
            <div class="card-header"><h2 class="card-title">Top clientes</h2></div>
            <table class="table"><thead><tr><th>Cliente</th><th class="text-center">Facturas</th><th class="text-right">Total</th><th class="text-right">Saldo</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@forelse ($data['byCustomer'] as $row)<tr><td>{{ $row['name'] }}</td><td class="text-center">{{ $row['count'] }}</td><td class="text-right font-medium">{{ money($row['total']) }}</td><td class="text-right">{{ money($row['balance']) }}</td></tr>@empty<tr><td colspan="4" class="py-6 text-center text-slate-500">Sin datos.</td></tr>@endforelse</tbody></table>
        </div>
        <div class="card overflow-x-auto">
            <div class="card-header"><h2 class="card-title">Top productos (base imponible)</h2></div>
            <table class="table"><thead><tr><th>Producto / concepto</th><th class="text-right">Cant.</th><th class="text-right">Ventas</th><th class="text-right">Margen</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@forelse ($data['byProduct'] as $row)<tr><td>{{ $row->description }}</td><td class="text-right">{{ qty($row->quantity) }}</td><td class="text-right font-medium">{{ money($row->subtotal) }}</td><td class="text-right text-emerald-600">{{ money($row->subtotal - $row->cost) }}</td></tr>@empty<tr><td colspan="4" class="py-6 text-center text-slate-500">Sin datos.</td></tr>@endforelse</tbody></table>
        </div>
    </div>
    <div class="card overflow-x-auto">
        <div class="card-header"><h2 class="card-title">Detalle de facturas</h2></div>
        <table class="table">
            <thead><tr><th>Número</th><th>Fecha</th><th>Cliente</th><th class="text-right">Base</th><th class="text-right">Impuestos</th><th class="text-right">Total</th><th class="text-right">Saldo</th><th>Estado</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($data['invoices'] as $d)
                    <tr><td><a class="link" href="{{ route('invoices.show', $d) }}">{{ $d->number }}</a></td><td>{{ fdate($d->date) }}</td><td>{{ $d->customer?->name }}</td><td class="text-right">{{ money($d->subtotal) }}</td><td class="text-right">{{ money($d->tax_total) }}</td><td class="text-right font-medium">{{ money($d->total) }}</td><td class="text-right">{{ money($d->balance) }}</td><td><x-badge :color="$d->statusColor()">{{ $d->statusLabel() }}</x-badge></td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="3">Totales</td><td class="text-right">{{ money($data['subtotal']) }}</td><td class="text-right">{{ money($data['tax']) }}</td><td class="text-right">{{ money($data['total']) }}</td><td class="text-right">{{ money($data['pending']) }}</td><td></td></tr></tfoot>
        </table>
    </div>
</x-app-layout>
