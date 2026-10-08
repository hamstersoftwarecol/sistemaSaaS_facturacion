<x-app-layout :title="$supplier->name">
    <x-page-header :title="$supplier->name" :subtitle="collect([$supplier->tax_id, $supplier->contact_name, $supplier->email])->filter()->implode(' · ')" :back="route('suppliers.index')">
        <a href="{{ route('suppliers.ledger', $supplier) }}" class="btn-secondary"><x-icon name="list" class="h-4 w-4"/> Estado de cuenta</a>
        @can('purchase_orders.create')<a href="{{ route('purchase-orders.create', ['supplier_id' => $supplier->id]) }}" class="btn-secondary"><x-icon name="shopping-cart" class="h-4 w-4"/> Orden de compra</a>@endcan
        @can('payments_made.create')<a href="{{ route('payments-made.create', ['supplier_id' => $supplier->id]) }}" class="btn-secondary"><x-icon name="send" class="h-4 w-4"/> Registrar pago</a>@endcan
        @can('suppliers.edit')<a href="{{ route('suppliers.edit', $supplier) }}" class="btn-primary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>@endcan
    </x-page-header>
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Saldo por pagar" :value="money($balance)" icon="clock" :color="$balance > 0 ? 'yellow' : 'green'"/>
        <x-stat label="Compras recibidas" :value="money($purchased)" icon="shopping-cart" color="indigo"/>
        <x-stat label="Total pagado" :value="money($paid)" icon="banknotes" color="green"/>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Documentos de compra</h2></div>
            @if ($documents->isEmpty())
                <p class="p-5 text-sm text-slate-500">Sin documentos.</p>
            @else
                <table class="table">
                    <thead><tr><th>Documento</th><th>Fecha</th><th class="text-right">Total</th><th class="text-right">Saldo</th><th>Estado</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documents as $doc)
                            <tr>
                                <td><span class="text-xs text-slate-400">{{ $doc->cfg('label') }}</span><br><a class="link" href="{{ route($doc->routeName('show'), $doc) }}">{{ $doc->number }}</a></td>
                                <td>{{ fdate($doc->date) }}</td>
                                <td class="text-right">{{ money($doc->total) }}</td>
                                <td class="text-right">{{ $doc->type === 'purchase_order' ? money($doc->balance) : '—' }}</td>
                                <td><x-badge :color="$doc->statusColor()">{{ $doc->statusLabel() }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="space-y-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Últimos pagos</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($payments as $payment)
                        <li class="flex justify-between px-5 py-3"><a class="link" href="{{ route('payments-made.show', $payment) }}">{{ $payment->number }}</a><span class="text-slate-500">{{ fdate($payment->date) }}</span><span class="font-medium">{{ money($payment->amount) }}</span></li>
                    @empty
                        <li class="px-5 py-4 text-slate-500">Sin pagos registrados.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card card-body text-sm">
                <dl class="space-y-2">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Dirección</dt><dd class="text-right">{{ collect([$supplier->address, $supplier->city, $supplier->country])->filter()->implode(', ') ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Teléfono</dt><dd>{{ $supplier->phone ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Plazo de pago</dt><dd>{{ $supplier->payment_terms }} días</dd></div>
                    @foreach ($customFields as $field)
                        @if (filled($supplier->custom_fields[$field->name] ?? null))
                            <div class="flex justify-between"><dt class="text-slate-500">{{ $field->label }}</dt><dd>{{ $field->displayValue($supplier->custom_fields[$field->name]) }}</dd></div>
                        @endif
                    @endforeach
                </dl>
            </div>
            @can('suppliers.delete')<x-delete-button :action="route('suppliers.destroy', $supplier)" label="Eliminar proveedor" class="w-full"/>@endcan
        </div>
    </div>
</x-app-layout>
