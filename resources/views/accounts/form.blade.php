<x-app-layout :title="$account->exists ? 'Editar cuenta' : 'Nueva cuenta'">
    <x-page-header :title="$account->exists ? 'Editar '.$account->name : 'Nueva cuenta de efectivo o banco'" :back="route('accounts.index')"/>
    <form method="POST" action="{{ $account->exists ? route('accounts.update', $account) : route('accounts.store') }}" class="max-w-3xl" x-data="{ type: @js(old('type', $account->type)) }">
        @csrf
        @if ($account->exists) @method('PUT') @endif
        <div class="card card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Nombre" :value="$account->name" required placeholder="Caja principal, Bancolombia corriente…"/>
            <div><label class="form-label">Tipo</label><select name="type" x-model="type" class="form-select">@foreach (\App\Models\Account::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            <template x-if="type === 'bank'"><div class="contents">
                <x-form.input name="bank_name" label="Banco" :value="$account->bank_name"/>
                <x-form.input name="account_number" label="Número de cuenta" :value="$account->account_number"/>
            </div></template>
            <x-form.select name="branch_id" label="Sucursal" :options="$branches->pluck('name', 'id')->all()" :value="$account->branch_id" placeholder="Todas / empresa"/>
            <x-form.input name="opening_balance" type="number" step="0.01" label="Saldo inicial" :value="$account->opening_balance ?? 0"/>
            <x-form.textarea name="notes" label="Notas" :value="$account->notes" class="sm:col-span-2" rows="2"/>
            <x-form.checkbox name="is_active" label="Cuenta activa" :checked="$account->is_active"/>
        </div>
        <div class="mt-5 flex gap-3">
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar</button>
            @if ($account->exists)
                @can('accounts.delete')<x-delete-button :action="route('accounts.destroy', $account)"/>@endcan
            @endif
        </div>
    </form>
</x-app-layout>
