<x-app-layout :title="$supplier->exists ? 'Editar proveedor' : 'Nuevo proveedor'">
    <x-page-header :title="$supplier->exists ? 'Editar '.$supplier->name : 'Nuevo proveedor'" :back="route('suppliers.index')"/>
    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="max-w-4xl space-y-6">
        @csrf
        @if ($supplier->exists) @method('PUT') @endif
        <div class="card card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Nombre o razón social" :value="$supplier->name" required class="sm:col-span-2"/>
            <x-form.input name="tax_id" label="NIT / RUC" :value="$supplier->tax_id"/>
            <x-form.input name="code" label="Código interno" :value="$supplier->code"/>
            <x-form.input name="contact_name" label="Persona de contacto" :value="$supplier->contact_name"/>
            <x-form.input name="email" type="email" label="Correo electrónico" :value="$supplier->email"/>
            <x-form.input name="phone" label="Teléfono" :value="$supplier->phone"/>
            <x-form.input name="city" label="Ciudad" :value="$supplier->city"/>
            <x-form.input name="address" label="Dirección" :value="$supplier->address" class="sm:col-span-2"/>
            <x-form.input name="country" label="País" :value="$supplier->country"/>
            <x-form.input name="payment_terms" type="number" label="Plazo de pago (días)" :value="$supplier->payment_terms ?? 30"/>
            <x-form.input name="opening_balance" type="number" step="0.01" label="Saldo inicial por pagar" :value="$supplier->opening_balance"/>
            <x-form.checkbox name="is_active" label="Proveedor activo" :checked="$supplier->is_active" class="pt-6"/>
            <x-form.textarea name="notes" label="Notas" :value="$supplier->notes" class="sm:col-span-2" rows="2"/>
        </div>
        @if ($customFields->isNotEmpty())
            <div class="card card-body"><x-custom-fields :fields="$customFields" :values="$supplier->custom_fields ?? []"/></div>
        @endif
        <div class="flex gap-3">
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar proveedor</button>
            <a href="{{ route('suppliers.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-app-layout>
