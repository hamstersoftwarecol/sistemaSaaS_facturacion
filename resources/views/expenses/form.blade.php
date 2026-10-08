<x-app-layout :title="$expense->exists ? 'Editar gasto' : 'Nuevo gasto'">
    <x-page-header :title="$expense->exists ? 'Editar gasto' : 'Registrar gasto'" :back="route('expenses.index')"/>
    <form method="POST" action="{{ $expense->exists ? route('expenses.update', $expense) : route('expenses.store') }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        @if ($expense->exists) @method('PUT') @endif
        <x-form.input name="description" label="Descripción" :value="$expense->description" required class="sm:col-span-2"/>
        <x-form.input name="amount" type="number" step="0.01" label="Importe total" :value="$expense->amount" required/>
        <x-form.input name="tax_amount" type="number" step="0.01" label="Impuesto incluido (soportado)" :value="$expense->tax_amount" hint="Se usa en el informe de impuestos."/>
        <x-form.input name="date" type="date" label="Fecha" :value="$expense->date" required/>
        <x-form.select name="category_id" label="Categoría" :options="$categories->pluck('name', 'id')->all()" :value="$expense->category_id" placeholder="Sin categoría"/>
        <x-form.select name="account_id" label="Pagado desde" :options="$accounts->pluck('name', 'id')->all()" :value="$expense->account_id ?? $accounts->first()?->id" required/>
        <x-form.select name="supplier_id" label="Proveedor" :options="$suppliers->pluck('name', 'id')->all()" :value="$expense->supplier_id" placeholder="—"/>
        <x-form.input name="reference" label="Referencia / N.º factura" :value="$expense->reference" class="sm:col-span-2"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar gasto</button></div>
    </form>
</x-app-layout>
