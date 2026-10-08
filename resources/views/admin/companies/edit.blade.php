<x-admin-layout title="Editar empresa">
    <x-page-header :title="'Editar '.$company->name" :back="route('admin.companies.show', $company)"/>
    <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf @method('PUT')
        <x-form.input name="name" label="Nombre" :value="$company->name" required/>
        <x-form.input name="legal_name" label="Razón social" :value="$company->legal_name"/>
        <x-form.input name="tax_id" label="NIT" :value="$company->tax_id"/>
        <x-form.input name="email" type="email" label="Correo" :value="$company->email"/>
        <x-form.input name="phone" label="Teléfono" :value="$company->phone"/>
        <x-form.input name="country" label="País" :value="$company->country"/>
        <x-form.input name="currency" label="Moneda" :value="$company->currency" required/>
        <x-form.input name="currency_symbol" label="Símbolo" :value="$company->currency_symbol" required/>
        <x-form.select name="status" label="Estado" :options="['active' => 'Activa', 'suspended' => 'Suspendida']" :value="$company->status"/>
        <div class="sm:col-span-2 flex gap-3">
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar</button>
            <x-delete-button :action="route('admin.companies.destroy', $company)" label="Eliminar empresa" confirm="Se eliminarán la empresa y TODOS sus datos (documentos, usuarios, inventario…). ¿Continuar?"/>
        </div>
    </form>
</x-admin-layout>
