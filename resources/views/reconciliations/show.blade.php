<x-app-layout title="Conciliación">
    <x-page-header :title="'Conciliación · '.$reconciliation->account?->name" :subtitle="'Extracto al '.fdate($reconciliation->statement_date)" :back="route('reconciliations.index')">
        @can('reconciliations.delete')<x-delete-button :action="route('reconciliations.destroy', $reconciliation)" label="Deshacer conciliación" confirm="Los movimientos volverán a quedar pendientes de conciliar. ¿Continuar?"/>@endcan
    </x-page-header>
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Saldo anterior" :value="money($reconciliation->opening_balance)"/>
        <x-stat label="Saldo extracto" :value="money($reconciliation->statement_balance)" icon="bank" color="blue"/>
        <x-stat label="Saldo conciliado" :value="money($reconciliation->cleared_balance)" icon="scale"/>
        <x-stat label="Diferencia" :value="money($reconciliation->difference)" icon="warning" :color="abs($reconciliation->difference) < 0.01 ? 'green' : 'red'"/>
    </div>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Concepto</th><th>Tipo</th><th class="text-right">Entrada</th><th class="text-right">Salida</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($transactions as $t)
                    <tr><td>{{ fdate($t->date) }}</td><td>{{ $t->description }}</td><td><x-badge>{{ $t->typeLabel() }}</x-badge></td>
                        <td class="text-right text-emerald-600">{{ $t->direction === 'in' ? money($t->amount) : '' }}</td><td class="text-right text-rose-600">{{ $t->direction === 'out' ? money($t->amount) : '' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($reconciliation->notes)<p class="mt-4 text-sm text-slate-600">{{ $reconciliation->notes }}</p>@endif
</x-app-layout>
