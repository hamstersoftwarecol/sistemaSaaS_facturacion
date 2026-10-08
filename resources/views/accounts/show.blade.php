<x-app-layout :title="$account->name">
    <x-page-header :title="$account->name" :subtitle="$account->typeLabel().($account->bank_name ? ' · '.$account->bank_name.' '.$account->account_number : '')" :back="route('accounts.index')">
        @can('reconciliations.create')@if ($account->type === 'bank')<a href="{{ route('reconciliations.create', ['account_id' => $account->id]) }}" class="btn-secondary"><x-icon name="scale" class="h-4 w-4"/> Conciliar</a>@endif @endcan
        @can('accounts.edit')<a href="{{ route('accounts.edit', $account) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>@endcan
    </x-page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Saldo actual" :value="money($balance)" icon="banknotes" color="green"/>
        <x-stat label="Saldo conciliado" :value="money($reconciled)" icon="scale" color="blue"/>
        <x-stat label="Entradas del mes" :value="money($monthIn)" icon="trending-up" color="green"/>
        <x-stat label="Salidas del mes" :value="money($monthOut)" icon="trending-down" color="red"/>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
                <div><label class="form-label">Desde</label><input type="date" name="from" value="{{ request('from') }}" class="form-input"></div>
                <div><label class="form-label">Hasta</label><input type="date" name="to" value="{{ request('to') }}" class="form-input"></div>
                <button class="btn-secondary">Filtrar</button>
            </form>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Concepto</th><th>Tipo</th><th class="text-right">Entrada</th><th class="text-right">Salida</th><th class="text-center">Conc.</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transactions as $t)
                            <tr>
                                <td class="whitespace-nowrap">{{ fdate($t->date) }}</td>
                                <td>{{ $t->description }}@if ($t->reference)<div class="text-xs text-slate-400">{{ $t->reference }}</div>@endif</td>
                                <td><x-badge>{{ $t->typeLabel() }}</x-badge></td>
                                <td class="text-right text-emerald-600">{{ $t->direction === 'in' ? money($t->amount) : '' }}</td>
                                <td class="text-right text-rose-600">{{ $t->direction === 'out' ? money($t->amount) : '' }}</td>
                                <td class="text-center">@if ($t->reconciled_at)<x-icon name="check" class="mx-auto h-4 w-4 text-emerald-600"/>@endif</td>
                                <td class="text-right">
                                    @if (! $t->source_type && ! $t->reconciled_at)
                                        @can('accounts.delete')<x-delete-button :action="route('accounts.transactions.destroy', $t)" :small="true" confirm="¿Eliminar este movimiento?"/>@endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-center text-slate-500">Sin movimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $transactions->links() }}</div>
        </div>

        @can('accounts.create')
            <div class="card card-body h-fit" x-data="{ type: 'deposit' }">
                <h2 class="card-title mb-4">Movimiento manual</h2>
                <form method="POST" action="{{ route('accounts.transactions.store', $account) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer rounded-lg border p-2 text-center text-sm" :class="type === 'deposit' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200'">
                            <input type="radio" name="type" value="deposit" x-model="type" class="sr-only"> Depósito
                        </label>
                        <label class="cursor-pointer rounded-lg border p-2 text-center text-sm" :class="type === 'withdrawal' ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-200'">
                            <input type="radio" name="type" value="withdrawal" x-model="type" class="sr-only"> Retiro
                        </label>
                    </div>
                    <x-form.input name="amount" type="number" step="0.01" label="Importe" required/>
                    <x-form.input name="date" type="date" label="Fecha" :value="today()" required/>
                    <x-form.input name="description" label="Descripción" required/>
                    <x-form.input name="reference" label="Referencia"/>
                    <button class="btn-primary w-full">Registrar</button>
                </form>
            </div>
        @endcan
    </div>
</x-app-layout>
