<x-app-layout title="Préstamos">
    <x-page-header title="Préstamos y cuotas" subtitle="Créditos recibidos y préstamos otorgados con su cuadro de amortización">
        @can('loans.create')<a href="{{ route('loans.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo préstamo</a>@endcan
    </x-page-header>
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Deuda pendiente (recibidos)" :value="money($borrowedOutstanding)" icon="trending-down" color="red"/>
        <x-stat label="Por cobrar (otorgados)" :value="money($lentOutstanding)" icon="trending-up" color="green"/>
        <x-stat label="Préstamos activos" :value="$loans->where('status', 'active')->count()" icon="dollar"/>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            @if ($loans->isEmpty())
                <x-empty-state icon="dollar" title="Sin préstamos" description="Registre créditos bancarios o préstamos a terceros y controle sus cuotas."/>
            @else
                <table class="table">
                    <thead><tr><th>Entidad / persona</th><th>Tipo</th><th class="text-right">Capital</th><th class="text-right">Tasa anual</th><th class="text-center">Cuotas</th><th class="text-right">Pendiente</th><th>Estado</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($loans as $loan)
                            <tr>
                                <td><a href="{{ route('loans.show', $loan) }}" class="link">{{ $loan->party_name }}</a><div class="text-xs text-slate-400">{{ $loan->reference }}</div></td>
                                <td><x-badge :color="$loan->type === 'borrowed' ? 'red' : 'green'">{{ $loan->type === 'borrowed' ? 'Recibido' : 'Otorgado' }}</x-badge></td>
                                <td class="text-right">{{ money($loan->principal) }}</td>
                                <td class="text-right">{{ pct($loan->interest_rate) }}</td>
                                <td class="text-center">{{ $loan->installments->where('status', 'paid')->count() }} / {{ $loan->installments_count }}</td>
                                <td class="text-right font-semibold">{{ money($loan->outstanding()) }}</td>
                                <td><x-badge :color="$loan->statusColor()">{{ $loan->statusLabel() }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="card h-fit">
            <div class="card-header"><h2 class="card-title">Próximas cuotas</h2></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($upcoming as $inst)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div><a class="font-medium hover:text-indigo-600" href="{{ route('loans.show', $inst->loan_id) }}">{{ $inst->loan->party_name }}</a>
                            <p class="text-xs {{ $inst->isOverdue() ? 'text-rose-600 font-semibold' : 'text-slate-400' }}">Cuota {{ $inst->number }} · {{ fdate($inst->due_date) }}{{ $inst->isOverdue() ? ' · vencida' : '' }}</p></div>
                        <span class="font-semibold">{{ money($inst->amount) }}</span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-slate-500">No hay cuotas pendientes.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
