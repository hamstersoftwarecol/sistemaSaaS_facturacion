@php
    $rows = $transactions->map(fn ($t) => ['id' => $t->id, 'amount' => $t->signedAmount()])->values();
@endphp
<x-app-layout title="Nueva conciliación">
    <x-page-header title="Nueva conciliación bancaria" subtitle="1) Elija la cuenta y los datos del extracto · 2) Marque los movimientos que aparecen en el extracto · 3) Guarde cuando la diferencia sea cero" :back="route('reconciliations.index')"/>

    <form method="GET" class="card card-body mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
        <div><label class="form-label">Cuenta</label>
            <select name="account_id" class="form-select" required><option value="">Seleccione…</option>@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($account?->id === $a->id)>{{ $a->name }}</option>@endforeach</select></div>
        <div><label class="form-label">Fecha del extracto</label><input type="date" name="statement_date" value="{{ $statementDate }}" class="form-input" required></div>
        <div><label class="form-label">Saldo final del extracto</label><input type="number" step="0.01" name="statement_balance" value="{{ $statementBalance }}" class="form-input" required></div>
        <button class="btn-secondary">Cargar movimientos</button>
    </form>

    @if ($account)
        <form method="POST" action="{{ route('reconciliations.store') }}"
              x-data="{ opening: {{ (float) $openingBalance }}, statement: {{ (float) ($statementBalance ?? 0) }}, rows: @js($rows), checked: [],
                        get cleared() { return this.opening + this.rows.filter(r => this.checked.includes(String(r.id))).reduce((s, r) => s + r.amount, 0) },
                        get diff() { return Math.round((this.statement - this.cleared) * 100) / 100 },
                        toggleAll(e) { this.checked = e.target.checked ? this.rows.map(r => String(r.id)) : [] } }">
            @csrf
            <input type="hidden" name="account_id" value="{{ $account->id }}">
            <input type="hidden" name="statement_date" value="{{ $statementDate }}">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="card overflow-x-auto xl:col-span-2">
                    <table class="table">
                        <thead><tr><th class="w-10"><input type="checkbox" class="form-checkbox" @change="toggleAll($event)"></th><th>Fecha</th><th>Concepto</th><th class="text-right">Entrada</th><th class="text-right">Salida</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($transactions as $t)
                                <tr>
                                    <td><input type="checkbox" name="transactions[]" value="{{ $t->id }}" x-model="checked" class="form-checkbox"></td>
                                    <td class="whitespace-nowrap">{{ fdate($t->date) }}</td>
                                    <td>{{ $t->description }}</td>
                                    <td class="text-right text-emerald-600">{{ $t->direction === 'in' ? money($t->amount) : '' }}</td>
                                    <td class="text-right text-rose-600">{{ $t->direction === 'out' ? money($t->amount) : '' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-slate-500">No hay movimientos pendientes de conciliar hasta esa fecha.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card card-body h-fit space-y-3 text-sm xl:sticky xl:top-20">
                    <div class="flex justify-between"><span class="text-slate-500">Saldo conciliado anterior</span><span class="font-medium" x-text="formatMoney(opening)"></span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Saldo según extracto</span>
                        <input type="number" step="0.01" name="statement_balance" x-model.number="statement" class="form-input w-36 py-1 text-right"></div>
                    <div class="flex justify-between"><span class="text-slate-500">Saldo de lo marcado</span><span class="font-medium" x-text="formatMoney(cleared)"></span></div>
                    <div class="flex justify-between rounded-lg p-3 text-base font-semibold" :class="Math.abs(diff) < 0.01 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                        <span>Diferencia</span><span x-text="formatMoney(diff)"></span>
                    </div>
                    <x-form.textarea name="notes" label="Notas" rows="2"/>
                    <button class="btn-primary w-full">Guardar conciliación</button>
                    <p class="text-xs text-slate-400">Puede guardar con diferencia; quedará registrada para su revisión.</p>
                </div>
            </div>
        </form>
    @endif
</x-app-layout>
