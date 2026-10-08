<x-app-layout :title="$income->exists ? 'Editar ingreso' : 'Nuevo ingreso'">
    <x-page-header :title="$income->exists ? 'Editar ingreso' : 'Registrar otro ingreso'" :back="route('incomes.index')"/>
    <form method="POST" action="{{ $income->exists ? route('incomes.update', $income) : route('incomes.store') }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        @if ($income->exists) @method('PUT') @endif
        <x-form.input name="description" label="Descripción" :value="$income->description" required class="sm:col-span-2"/>
        <x-form.input name="amount" type="number" step="0.01" label="Importe" :value="$income->amount" required/>
        <x-form.input name="date" type="date" label="Fecha" :value="$income->date" required/>
        <x-form.select name="category_id" label="Categoría" :options="$categories->pluck('name', 'id')->all()" :value="$income->category_id" placeholder="Sin categoría"/>
        <x-form.select name="account_id" label="Ingresado en" :options="$accounts->pluck('name', 'id')->all()" :value="$income->account_id ?? $accounts->first()?->id" required/>
        <x-form.select name="customer_id" label="Cliente / pagador" :options="$customers->pluck('name', 'id')->all()" :value="$income->customer_id" placeholder="—"/>
        <x-form.input name="reference" label="Referencia" :value="$income->reference"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar ingreso</button></div>
    </form>
</x-app-layout>
