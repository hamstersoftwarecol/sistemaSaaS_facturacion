@php
    $partyKey = $meta['party'].'_id';
    $isEdit = $payment->exists;
    $docs = $documents->map(fn ($d) => ['id' => $d->id, 'number' => $d->number, 'party' => $d->{$partyKey}, 'balance' => (float) $d->balance, 'total' => (float) $d->total, 'due' => fdate($d->due_date)])->values();
@endphp
<x-app-layout :title="$isEdit ? 'Editar pago' : 'Registrar pago'">
    <x-page-header :title="$isEdit ? 'Editar '.mb_strtolower($meta['label']).' '.$payment->number : 'Registrar '.mb_strtolower($meta['label'])" :back="route($meta['route'].'.index')"/>

    <form method="POST" action="{{ $isEdit ? route($meta['route'].'.update', $payment) : route($meta['route'].'.store') }}" class="max-w-3xl"
          x-data="{ party: @js((string) old($partyKey, $payment->{$partyKey})), doc: @js((string) old('document_id', $payment->document_id)), amount: @js(old('amount', $payment->amount)), docs: @js($docs),
                    get available() { return this.docs.filter(d => !this.party || String(d.party) === String(this.party)) },
                    pick() { const d = this.docs.find(x => String(x.id) === String(this.doc)); if (d) { this.party = String(d.party); if (!this.amount || Number(this.amount) === 0) this.amount = d.balance } } }">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <div class="card card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="form-label">{{ $meta['party_label'] }} <span class="text-rose-500">*</span></label>
                <select name="{{ $partyKey }}" class="form-select" x-model="party" required>
                    <option value="">Seleccione…</option>
                    @foreach ($parties as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
                @error($partyKey)<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">{{ $meta['doc_label'] }} a pagar</label>
                <select name="document_id" class="form-select" x-model="doc" @change="pick()">
                    <option value="">— Pago a cuenta / anticipo —</option>
                    <template x-for="d in available" :key="d.id">
                        <option :value="String(d.id)" :selected="String(d.id) === String(doc)" x-text="`${d.number} · saldo ${formatMoney(d.balance)} · vence ${d.due}`"></option>
                    </template>
                </select>
                @error('document_id')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label">Importe <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" class="form-input text-lg font-semibold" required>
                @error('amount')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <x-form.input name="date" type="date" label="Fecha" :value="$payment->date" required/>
            <x-form.select name="method" label="Método de pago" :options="\App\Models\Payment::METHODS" :value="$payment->method" required/>
            <x-form.select name="account_id" label="Cuenta de {{ $meta['party'] === 'customer' ? 'ingreso' : 'salida' }}" :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->name.' ('.$a->typeLabel().')'])->all()"
                :value="$payment->account_id ?? $accounts->first()?->id" required hint="El movimiento se registrará en esta cuenta de efectivo o banco."/>
            <x-form.input name="reference" label="Referencia" :value="$payment->reference" placeholder="N.º de transferencia, voucher…" class="sm:col-span-2"/>
            <x-form.textarea name="notes" label="Notas" :value="$payment->notes" class="sm:col-span-2" rows="2"/>
        </div>
        <div class="mt-5 flex gap-3">
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar pago</button>
            <a href="{{ route($meta['route'].'.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-app-layout>
