<x-app-layout title="Transferencia entre cuentas">
    <x-page-header title="Transferencia entre cuentas" subtitle="Mueva fondos entre cajas y bancos" :back="route('accounts.index')"/>
    <form method="POST" action="{{ route('accounts.transfer.store') }}" class="card card-body grid max-w-2xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        <x-form.select name="from_account_id" label="Desde" :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->name.' ('.money($a->balance()).')'])->all()" required/>
        <x-form.select name="to_account_id" label="Hacia" :options="$accounts->mapWithKeys(fn ($a) => [$a->id => $a->name])->all()" :value="$accounts->skip(1)->first()?->id" required/>
        <x-form.input name="amount" type="number" step="0.01" label="Importe" required/>
        <x-form.input name="date" type="date" label="Fecha" :value="today()" required/>
        <x-form.input name="description" label="Descripción" class="sm:col-span-2" placeholder="Consignación de caja al banco…"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="switch" class="h-4 w-4"/> Transferir</button></div>
    </form>
</x-app-layout>
