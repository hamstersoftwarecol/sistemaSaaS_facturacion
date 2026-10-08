<x-app-layout title="Facturas recurrentes">
    <x-page-header title="Facturas recurrentes" subtitle="Suscripciones, igualas y cuotas que se facturan automáticamente">
        @can('recurring_invoices.create')<a href="{{ route('recurring-invoices.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nueva plantilla</a>@endcan
    </x-page-header>
    <div class="mb-5 rounded-lg bg-sky-50 p-4 text-sm text-sky-800 ring-1 ring-sky-200">
        <strong>Automático:</strong> el programador de tareas (<code>php artisan schedule:run</code>) ejecuta cada día <code>invoices:recurring</code> y emite las facturas vencidas. También puede generarlas manualmente.
    </div>
    <div class="card overflow-x-auto">
        @if ($recurrings->isEmpty())
            <x-empty-state icon="refresh" title="Sin facturas recurrentes" description="Programe facturas periódicas para sus clientes con contratos o suscripciones."/>
        @else
            <table class="table">
                <thead><tr><th>Plantilla</th><th>Cliente</th><th>Frecuencia</th><th>Próxima emisión</th><th class="text-right">Importe</th><th class="text-center">Emitidas</th><th>Estado</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($recurrings as $r)
                        <tr>
                            <td><a href="{{ route('recurring-invoices.show', $r) }}" class="link">{{ $r->title }}</a></td>
                            <td>{{ $r->customer?->name }}</td>
                            <td>{{ $r->frequencyLabel() }}</td>
                            <td class="{{ $r->isDue() ? 'font-semibold text-amber-600' : '' }}">{{ $r->status === 'completed' ? '—' : fdate($r->next_date) }}</td>
                            <td class="text-right font-medium">{{ money($r->total) }}</td>
                            <td class="text-center">{{ $r->invoices_count }}{{ $r->max_occurrences ? ' / '.$r->max_occurrences : '' }}</td>
                            <td><x-badge :color="$r->statusColor()">{{ $r->statusLabel() }}</x-badge></td>
                            <td class="text-right">
                                @can('recurring_invoices.create')
                                    @if ($r->status === 'active')
                                        <form method="POST" action="{{ route('recurring-invoices.generate', $r) }}" class="inline">@csrf<button class="btn-ghost btn-sm" title="Generar ahora"><x-icon name="bolt" class="h-4 w-4"/></button></form>
                                    @endif
                                @endcan
                                @can('recurring_invoices.edit')<a href="{{ route('recurring-invoices.edit', $r) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a>@endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="border-t border-slate-100 p-4">{{ $recurrings->links() }}</div>
        @endif
    </div>
</x-app-layout>
