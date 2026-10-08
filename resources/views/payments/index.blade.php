@php $module = $meta['route'] === 'payments-received' ? 'payments_received' : 'payments_made'; @endphp
<x-app-layout :title="$meta['title']">
    <x-page-header :title="$meta['title']" :subtitle="$meta['party'] === 'customer' ? 'Cobros de facturas y anticipos de clientes' : 'Pagos realizados a proveedores'">
        @can($module.'.create')
            <a href="{{ route($meta['route'].'.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Registrar pago</a>
        @endcan
    </x-page-header>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="min-w-[220px] flex-1"><label class="form-label">Buscar</label><input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Número, referencia o {{ mb_strtolower($meta['party_label']) }}"></div>
            <div><label class="form-label">Método</label>
                <select name="method" class="form-select w-40"><option value="">Todos</option>
                    @foreach (\App\Models\Payment::METHODS as $key => $label)<option value="{{ $key }}" @selected(request('method') === $key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div><label class="form-label">Desde</label><input type="date" name="from" value="{{ request('from') }}" class="form-input"></div>
            <div><label class="form-label">Hasta</label><input type="date" name="to" value="{{ request('to') }}" class="form-input"></div>
            <button class="btn-secondary"><x-icon name="search" class="h-4 w-4"/> Filtrar</button>
            <div class="ml-auto text-right"><p class="text-xs text-slate-500">Total filtrado</p><p class="text-lg font-semibold">{{ money($total) }}</p></div>
        </form>

        @if ($payments->isEmpty())
            <x-empty-state icon="banknotes" title="Sin pagos registrados" description="Los pagos que registre aparecerán aquí y se reflejarán en caja y bancos."/>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Número</th><th>Fecha</th><th>{{ $meta['party_label'] }}</th><th>{{ $meta['doc_label'] }}</th><th>Método</th><th>Cuenta</th><th class="text-right">Importe</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($payments as $payment)
                            <tr>
                                <td><a href="{{ route($meta['route'].'.show', $payment) }}" class="link">{{ $payment->number }}</a></td>
                                <td>{{ fdate($payment->date) }}</td>
                                <td>{{ $payment->party()?->name }}</td>
                                <td>@if ($payment->document)<a class="link" href="{{ route($payment->document->routeName('show'), $payment->document) }}">{{ $payment->document->number }}</a>@else<span class="text-slate-400">Anticipo / a cuenta</span>@endif</td>
                                <td>{{ $payment->methodLabel() }}</td>
                                <td>{{ $payment->account?->name }}</td>
                                <td class="text-right font-semibold">{{ money($payment->amount) }}</td>
                                <td class="text-right"><a href="{{ route($meta['route'].'.receipt', $payment) }}" target="_blank" class="btn-ghost btn-sm" title="Recibo"><x-icon name="printer" class="h-4 w-4"/></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $payments->links() }}</div>
        @endif
    </div>
</x-app-layout>
