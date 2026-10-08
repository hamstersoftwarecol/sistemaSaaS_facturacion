<x-app-layout :title="$recurring->title">
    <x-page-header :title="$recurring->title" :subtitle="$recurring->customer?->name.' · '.$recurring->frequencyLabel()" :back="route('recurring-invoices.index')">
        @can('recurring_invoices.create')
            @if ($recurring->status === 'active')
                <form method="POST" action="{{ route('recurring-invoices.generate', $recurring) }}">@csrf<button class="btn-secondary"><x-icon name="bolt" class="h-4 w-4"/> Generar factura ahora</button></form>
            @endif
        @endcan
        @can('recurring_invoices.edit')<a href="{{ route('recurring-invoices.edit', $recurring) }}" class="btn-primary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>@endcan
        @can('recurring_invoices.delete')<x-delete-button :action="route('recurring-invoices.destroy', $recurring)"/>@endcan
    </x-page-header>
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Estado" :value="$recurring->statusLabel()" icon="refresh" :color="$recurring->statusColor()"/>
        <x-stat label="Próxima emisión" :value="$recurring->status === 'completed' ? '—' : fdate($recurring->next_date)" icon="clock" color="blue"/>
        <x-stat label="Importe por factura" :value="money($recurring->total)" icon="banknotes" color="green"/>
        <x-stat label="Facturas emitidas" :value="$recurring->occurrences.($recurring->max_occurrences ? ' / '.$recurring->max_occurrences : '')" icon="document"/>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Conceptos facturados</h2></div>
            <table class="table">
                <thead><tr><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Precio</th><th>Impuesto</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($recurring->items as $item)
                        <tr><td>{{ $item['description'] ?: \App\Models\Product::find($item['product_id'] ?? 0)?->name }}</td><td class="text-right">{{ qty($item['quantity']) }}</td><td class="text-right">{{ money($item['unit_price']) }}</td><td>{{ $taxRates[$item['tax_rate_id'] ?? 0] ?? '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Historial de facturas</h2></div>
            <table class="table">
                <thead><tr><th>Factura</th><th>Fecha</th><th class="text-right">Total</th><th>Estado</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($invoices as $invoice)
                        <tr><td><a class="link" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td><td>{{ fdate($invoice->date) }}</td><td class="text-right">{{ money($invoice->total) }}</td><td><x-badge :color="$invoice->statusColor()">{{ $invoice->statusLabel() }}</x-badge></td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-500">Todavía no se ha emitido ninguna factura.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
