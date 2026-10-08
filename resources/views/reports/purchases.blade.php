<x-app-layout title="Informe de compras">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Compras recibidas" :value="money($data['total'])" icon="shopping-cart" :hint="'Impuestos: '.money($data['tax'])"/>
        <x-stat label="Pagado" :value="money($data['paid'])" icon="banknotes" color="green"/>
        <x-stat label="Pendiente de pago" :value="money($data['pending'])" icon="clock" color="yellow"/>
        <x-stat label="Pendiente de recibir" :value="money($data['pendingReception'])" icon="truck" color="blue" :hint="'Devoluciones: '.money($data['returns'])"/>
    </div>
    <div class="mb-6 card overflow-x-auto">
        <div class="card-header"><h2 class="card-title">Compras por proveedor</h2></div>
        <table class="table"><thead><tr><th>Proveedor</th><th class="text-center">Órdenes</th><th class="text-right">Total</th><th class="text-right">Saldo</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@forelse ($data['bySupplier'] as $row)<tr><td>{{ $row['name'] }}</td><td class="text-center">{{ $row['count'] }}</td><td class="text-right font-medium">{{ money($row['total']) }}</td><td class="text-right">{{ money($row['balance']) }}</td></tr>@empty<tr><td colspan="4" class="py-6 text-center text-slate-500">Sin compras recibidas en el periodo.</td></tr>@endforelse</tbody></table>
    </div>
    <div class="card overflow-x-auto">
        <div class="card-header"><h2 class="card-title">Órdenes de compra del periodo</h2></div>
        <table class="table"><thead><tr><th>Número</th><th>Fecha</th><th>Proveedor</th><th>Estado</th><th class="text-right">Total</th><th class="text-right">Saldo</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@foreach ($data['orders'] as $d)<tr><td><a class="link" href="{{ route('purchase-orders.show', $d) }}">{{ $d->number }}</a></td><td>{{ fdate($d->date) }}</td><td>{{ $d->supplier?->name }}</td><td><x-badge :color="$d->statusColor()">{{ $d->statusLabel() }}</x-badge></td><td class="text-right">{{ money($d->total) }}</td><td class="text-right">{{ $d->status === 'received' ? money($d->balance) : '—' }}</td></tr>@endforeach</tbody></table>
    </div>
</x-app-layout>
