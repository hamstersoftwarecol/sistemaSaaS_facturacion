<x-app-layout :title="$customer->name">
    <x-page-header :title="$customer->name" :subtitle="collect([$customer->tax_id, $customer->email, $customer->phone])->filter()->implode(' · ')" :back="route('customers.index')">
        <a href="{{ route('customers.ledger', $customer) }}" class="btn-secondary"><x-icon name="list" class="h-4 w-4"/> Estado de cuenta</a>
        @can('invoices.create')<a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn-secondary"><x-icon name="document" class="h-4 w-4"/> Facturar</a>@endcan
        @can('payments_received.create')<a href="{{ route('payments-received.create', ['customer_id' => $customer->id]) }}" class="btn-secondary"><x-icon name="banknotes" class="h-4 w-4"/> Registrar cobro</a>@endcan
        @can('customers.edit')<a href="{{ route('customers.edit', $customer) }}" class="btn-primary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>@endcan
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat label="Saldo pendiente" :value="money($balance)" icon="clock" :color="$balance > 0 ? 'yellow' : 'green'"/>
        <x-stat label="Vencido" :value="money($overdue)" icon="warning" color="red"/>
        <x-stat label="Total facturado" :value="money($invoiced)" icon="document" color="indigo"/>
        <x-stat label="Total cobrado" :value="money($paid)" icon="banknotes" color="green"/>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Documentos recientes</h2></div>
            @if ($documents->isEmpty())
                <p class="p-5 text-sm text-slate-500">Este cliente aún no tiene documentos.</p>
            @else
                <table class="table">
                    <thead><tr><th>Documento</th><th>Fecha</th><th class="text-right">Total</th><th class="text-right">Saldo</th><th>Estado</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documents as $doc)
                            <tr>
                                <td><span class="text-xs text-slate-400">{{ $doc->cfg('label') }}</span><br><a class="link" href="{{ route($doc->routeName('show'), $doc) }}">{{ $doc->number }}</a></td>
                                <td>{{ fdate($doc->date) }}</td>
                                <td class="text-right">{{ money($doc->total) }}</td>
                                <td class="text-right">{{ $doc->type === 'invoice' ? money($doc->balance) : '—' }}</td>
                                <td><x-badge :color="$doc->statusColor()">{{ $doc->statusLabel() }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="space-y-6">
            <div class="card card-body text-sm">
                <h2 class="card-title mb-3">Ficha</h2>
                <dl class="space-y-2">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Dirección</dt><dd class="text-right">{{ collect([$customer->address, $customer->city, $customer->country])->filter()->implode(', ') ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Lista de precios</dt><dd>{{ $customer->priceList?->name ?? 'Precio base' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Plazo de pago</dt><dd>{{ $customer->payment_terms }} días</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Límite de crédito</dt><dd>{{ money($customer->credit_limit) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Saldo inicial</dt><dd>{{ money($customer->opening_balance) }}</dd></div>
                    @foreach ($customFields as $field)
                        @if (filled($customer->custom_fields[$field->name] ?? null))
                            <div class="flex justify-between"><dt class="text-slate-500">{{ $field->label }}</dt><dd>{{ $field->displayValue($customer->custom_fields[$field->name]) }}</dd></div>
                        @endif
                    @endforeach
                </dl>
                @if ($customer->credit_limit > 0 && $balance > $customer->credit_limit)
                    <p class="mt-3 rounded bg-rose-50 p-2 text-xs text-rose-700">El saldo supera el límite de crédito.</p>
                @endif
                @if ($customer->notes)<p class="mt-3 rounded bg-slate-50 p-3 text-slate-600">{{ $customer->notes }}</p>@endif
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Últimos cobros</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($payments as $payment)
                        <li class="flex justify-between px-5 py-3"><a class="link" href="{{ route('payments-received.show', $payment) }}">{{ $payment->number }}</a><span class="text-slate-500">{{ fdate($payment->date) }}</span><span class="font-medium">{{ money($payment->amount) }}</span></li>
                    @empty
                        <li class="px-5 py-4 text-slate-500">Sin cobros registrados.</li>
                    @endforelse
                </ul>
            </div>
            @can('customers.delete')
                <x-delete-button :action="route('customers.destroy', $customer)" label="Eliminar cliente" class="w-full"/>
            @endcan
        </div>
    </div>
</x-app-layout>
