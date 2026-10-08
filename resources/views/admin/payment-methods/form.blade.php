<x-admin-layout :title="$method->exists ? 'Editar método' : 'Nuevo método'">
    <x-page-header :title="$method->exists ? 'Editar '.$method->name : 'Nuevo método de pago'" :back="route('admin.payment-methods.index')"/>
    <form method="POST" action="{{ $method->exists ? route('admin.payment-methods.update', $method) : route('admin.payment-methods.store') }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        @if ($method->exists) @method('PUT') @endif
        <x-form.input name="name" label="Nombre" :value="$method->name" required/>
        <x-form.select name="type" label="Tipo" :options="\App\Models\PaymentMethod::TYPES" :value="$method->type"/>
        <x-form.textarea name="instructions" label="Instrucciones para el cliente" :value="$method->instructions" rows="5" class="sm:col-span-2" hint="Datos de la cuenta, enlace de pago, etc."/>
        <x-form.input name="sort_order" type="number" label="Orden" :value="$method->sort_order ?? 0"/>
        <x-form.checkbox name="is_active" label="Activo" :checked="$method->is_active" class="pt-6"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar</button></div>
    </form>
</x-admin-layout>
