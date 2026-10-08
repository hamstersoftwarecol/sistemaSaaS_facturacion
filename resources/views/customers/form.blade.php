<x-app-layout :title="$customer->exists ? 'Editar cliente' : 'Nuevo cliente'">
    <x-page-header :title="$customer->exists ? 'Editar '.$customer->name : 'Nuevo cliente'" :back="$customer->exists ? route('customers.show', $customer) : route('customers.index')"/>
    <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="max-w-4xl space-y-6">
        @csrf
        @if ($customer->exists) @method('PUT') @endif
        <div class="card">
            <div class="card-header"><h2 class="card-title">Datos generales</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Nombre o razón social" :value="$customer->name" required class="sm:col-span-2"/>
                <x-form.input name="tax_id" label="NIT / RUC / identificación" :value="$customer->tax_id"/>
                <x-form.input name="code" label="Código interno" :value="$customer->code"/>
                <x-form.input name="email" type="email" label="Correo electrónico" :value="$customer->email"/>
                <x-form.input name="phone" label="Teléfono" :value="$customer->phone"/>
                <x-form.input name="address" label="Dirección" :value="$customer->address" class="sm:col-span-2"/>
                <x-form.input name="city" label="Ciudad" :value="$customer->city"/>
                <x-form.input name="country" label="País" :value="$customer->country"/>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Condiciones comerciales</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.select name="price_list_id" label="Lista de precios" :options="$priceLists->pluck('name', 'id')->all()" :value="$customer->price_list_id" placeholder="Precio base"/>
                <x-form.input name="payment_terms" type="number" label="Plazo de pago (días)" :value="$customer->payment_terms ?? 30" min="0"/>
                <x-form.input name="credit_limit" type="number" step="0.01" label="Límite de crédito" :value="$customer->credit_limit"/>
                <x-form.input name="opening_balance" type="number" step="0.01" label="Saldo inicial" :value="$customer->opening_balance" hint="Deuda previa del cliente al empezar a usar el sistema."/>
                <x-form.textarea name="notes" label="Notas internas" :value="$customer->notes" class="sm:col-span-2" rows="2"/>
                <x-form.checkbox name="is_active" label="Cliente activo" :checked="$customer->is_active"/>
            </div>
        </div>
        @if ($customFields->isNotEmpty())
            <div class="card">
                <div class="card-header"><h2 class="card-title">Campos personalizados</h2></div>
                <div class="card-body"><x-custom-fields :fields="$customFields" :values="$customer->custom_fields ?? []"/></div>
            </div>
        @endif
        <div class="flex gap-3">
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar cliente</button>
            <a href="{{ route('customers.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-app-layout>
