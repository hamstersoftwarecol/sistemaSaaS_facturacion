<x-app-layout title="Conciliación bancaria">
    <x-page-header title="Conciliación bancaria" subtitle="Compare sus movimientos con el extracto del banco">
        @can('reconciliations.create')<a href="{{ route('reconciliations.create') }}" class="btn-primary"><x-icon name="scale" class="h-4 w-4"/> Nueva conciliación</a>@endcan
    </x-page-header>
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach ($accounts->where('type', 'bank') as $account)
            <div class="card p-5">
                <p class="font-semibold">{{ $account->name }}</p>
                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div><p class="text-xs text-slate-400">Saldo en libros</p><p class="font-semibold">{{ money($account->balance()) }}</p></div>
                    <div><p class="text-xs text-slate-400">Conciliado</p><p class="font-semibold">{{ money($account->reconciledBalance()) }}</p></div>
                </div>
                @can('reconciliations.create')<a href="{{ route('reconciliations.create', ['account_id' => $account->id]) }}" class="mt-3 inline-block text-sm link">Conciliar →</a>@endcan
            </div>
        @endforeach
    </div>
    <div class="card overflow-x-auto">
        @if ($reconciliations->isEmpty())
            <x-empty-state icon="scale" title="Sin conciliaciones" description="Realice la primera conciliación con el extracto de su banco."/>
        @else
            <table class="table">
                <thead><tr><th>Fecha extracto</th><th>Cuenta</th><th class="text-right">Saldo extracto</th><th class="text-right">Saldo conciliado</th><th class="text-right">Diferencia</th><th class="text-center">Movimientos</th><th>Usuario</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($reconciliations as $r)
                        <tr>
                            <td><a href="{{ route('reconciliations.show', $r) }}" class="link">{{ fdate($r->statement_date) }}</a></td>
                            <td>{{ $r->account?->name }}</td>
                            <td class="text-right">{{ money($r->statement_balance) }}</td>
                            <td class="text-right">{{ money($r->cleared_balance) }}</td>
                            <td class="text-right font-semibold {{ abs($r->difference) < 0.01 ? 'text-emerald-600' : 'text-rose-600' }}">{{ money($r->difference) }}</td>
                            <td class="text-center">{{ $r->transactions_count }}</td>
                            <td class="text-slate-500">{{ $r->user?->name }}</td>
                            <td><x-badge :color="abs($r->difference) < 0.01 ? 'green' : 'yellow'">{{ abs($r->difference) < 0.01 ? 'Cuadrada' : 'Con diferencia' }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="border-t border-slate-100 p-4">{{ $reconciliations->links() }}</div>
        @endif
    </div>
</x-app-layout>
