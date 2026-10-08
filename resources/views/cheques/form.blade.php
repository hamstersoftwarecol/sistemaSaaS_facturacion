<x-app-layout :title="$cheque->exists ? 'Editar cheque' : 'Nuevo cheque'">
    <x-page-header :title="$cheque->exists ? 'Editar cheque #'.$cheque->cheque_number : 'Registrar cheque'" :back="route('cheques.index')"/>
    <form method="POST" action="{{ $cheque->exists ? route('cheques.update', $cheque) : route('cheques.store') }}" class="max-w-3xl" x-data="{ type: @js(old('type', $cheque->type)) }">
        @csrf
        @if ($cheque->exists) @method('PUT') @endif
        <div class="card card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div><label class="form-label">Tipo</label><select name="type" x-model="type" class="form-select">@foreach (\App\Models\Cheque::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <x-form.input name="cheque_number" label="Número de cheque" :value="$cheque->cheque_number" required/>
            <div x-show="type === 'received'"><x-form.select name="customer_id" label="Cliente" :options="$customers->pluck('name', 'id')->all()" :value="$cheque->customer_id" placeholder="—"/></div>
            <div x-show="type === 'issued'"><x-form.select name="supplier_id" label="Proveedor" :options="$suppliers->pluck('name', 'id')->all()" :value="$cheque->supplier_id" placeholder="—"/></div>
            <x-form.input name="party_name" label="Titular / beneficiario (si no está registrado)" :value="$cheque->party_name"/>
            <x-form.input name="bank_name" label="Banco" :value="$cheque->bank_name"/>
            <x-form.input name="amount" type="number" step="0.01" label="Importe" :value="$cheque->amount" required/>
            <x-form.input name="issue_date" type="date" label="Fecha de emisión" :value="$cheque->issue_date" required/>
            <x-form.input name="due_date" type="date" label="Fecha de cobro" :value="$cheque->due_date" required/>
            <x-form.select name="status" label="Estado" :options="collect(\App\Models\Cheque::STATUSES)->map(fn ($s) => $s[0])->all()" :value="$cheque->status"/>
            <x-form.select name="account_id" label="Cuenta bancaria" :options="$accounts->pluck('name', 'id')->all()" :value="$cheque->account_id" placeholder="—" hint="Al marcarlo como cobrado se registra el movimiento en esta cuenta."/>
            <x-form.textarea name="notes" label="Notas" :value="$cheque->notes" class="sm:col-span-2" rows="2"/>
        </div>
        <div class="mt-5 flex gap-3">
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar cheque</button>
            @if ($cheque->exists) @can('cheques.delete')<x-delete-button :action="route('cheques.destroy', $cheque)"/>@endcan @endif
        </div>
    </form>
</x-app-layout>
