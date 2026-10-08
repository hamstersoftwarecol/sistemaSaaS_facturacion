<x-app-layout :title="$loan->exists ? 'Editar préstamo' : 'Nuevo préstamo'">
    <x-page-header :title="$loan->exists ? 'Editar préstamo · '.$loan->party_name : 'Nuevo préstamo'" :back="route('loans.index')"/>
    <form method="POST" action="{{ $loan->exists ? route('loans.update', $loan) : route('loans.store') }}" class="max-w-3xl"
          x-data="{ p: {{ (float) old('principal', $loan->principal ?? 0) }}, r: {{ (float) old('interest_rate', $loan->interest_rate) }}, n: {{ (int) old('installments_count', $loan->installments_count) }}, f: @js(old('frequency', $loan->frequency)),
                    get payment() { const per = { weekly: 52, biweekly: 24, monthly: 12, quarterly: 4 }[this.f] || 12; const i = this.r / 100 / per; if (!this.n) return 0; return i > 0 ? this.p * i / (1 - Math.pow(1 + i, -this.n)) : this.p / this.n } }">
        @csrf
        @if ($loan->exists) @method('PUT') @endif
        <div class="card card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.select name="type" label="Tipo" :options="\App\Models\Loan::TYPES" :value="$loan->type" required/>
            <x-form.input name="party_name" label="Entidad o persona" :value="$loan->party_name" required placeholder="Banco, socio, empleado…"/>
            <div><label class="form-label">Capital *</label><input type="number" step="0.01" name="principal" x-model.number="p" class="form-input" required></div>
            <div><label class="form-label">Tasa de interés anual (%) *</label><input type="number" step="0.01" name="interest_rate" x-model.number="r" class="form-input" required></div>
            <div><label class="form-label">Número de cuotas *</label><input type="number" min="1" name="installments_count" x-model.number="n" class="form-input" required></div>
            <div><label class="form-label">Frecuencia</label><select name="frequency" x-model="f" class="form-select">@foreach (\App\Models\Loan::FREQUENCIES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <x-form.input name="start_date" type="date" label="Fecha de desembolso" :value="$loan->start_date" required/>
            <x-form.select name="account_id" label="Cuenta de desembolso / pago" :options="$accounts->pluck('name', 'id')->all()" :value="$loan->account_id" placeholder="Sin movimiento de caja"/>
            <x-form.input name="reference" label="Referencia / N.º de crédito" :value="$loan->reference"/>
            @if ($loan->exists)
                <x-form.select name="status" label="Estado" :options="collect(\App\Models\Loan::STATUSES)->map(fn ($s) => $s[0])->all()" :value="$loan->status"/>
            @endif
            <x-form.textarea name="notes" label="Notas" :value="$loan->notes" class="sm:col-span-2" rows="2"/>
            <div class="rounded-lg bg-indigo-50 p-4 text-sm text-indigo-900 sm:col-span-2">
                Cuota estimada (sistema francés): <strong x-text="formatMoney(payment)"></strong> · Total a pagar: <strong x-text="formatMoney(payment * n)"></strong>
            </div>
        </div>
        <div class="mt-5 flex gap-3">
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> {{ $loan->exists ? 'Guardar cambios' : 'Crear y generar cuotas' }}</button>
        </div>
    </form>
</x-app-layout>
