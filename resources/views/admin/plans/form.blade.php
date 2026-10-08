<x-admin-layout :title="$plan->exists ? 'Editar plan' : 'Nuevo plan'">
    <x-page-header :title="$plan->exists ? 'Editar plan '.$plan->name : 'Nuevo plan'" :back="route('admin.plans.index')"/>
    <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="card card-body grid max-w-4xl grid-cols-1 gap-5 sm:grid-cols-3">
        @csrf
        @if ($plan->exists) @method('PUT') @endif
        <x-form.input name="name" label="Nombre" :value="$plan->name" required/>
        <x-form.input name="slug" label="Identificador (slug)" :value="$plan->slug" hint="Se genera automáticamente si se deja vacío."/>
        <x-form.input name="sort_order" type="number" label="Orden" :value="$plan->sort_order ?? 0"/>
        <x-form.input name="description" label="Descripción" :value="$plan->description" class="sm:col-span-3"/>
        <x-form.input name="price_monthly" type="number" step="0.01" label="Precio mensual" :value="$plan->price_monthly ?? 0" required/>
        <x-form.input name="price_yearly" type="number" step="0.01" label="Precio anual" :value="$plan->price_yearly ?? 0" required/>
        <x-form.input name="trial_days" type="number" label="Días de prueba" :value="$plan->trial_days" required/>
        <x-form.input name="max_users" type="number" label="Máx. usuarios" :value="$plan->max_users" hint="Vacío = ilimitado"/>
        <x-form.input name="max_branches" type="number" label="Máx. sucursales" :value="$plan->max_branches" hint="Vacío = ilimitado"/>
        <x-form.input name="max_invoices" type="number" label="Máx. facturas/mes" :value="$plan->max_invoices" hint="Vacío = ilimitado"/>
        <x-form.input name="max_products" type="number" label="Máx. productos" :value="$plan->max_products" hint="Vacío = ilimitado"/>
        <x-form.textarea name="features" label="Características (una por línea)" :value="implode(PHP_EOL, $plan->features ?? [])" rows="5" class="sm:col-span-2"/>
        <div class="space-y-3 pt-6">
            <x-form.checkbox name="is_featured" label="Plan destacado" :checked="$plan->is_featured"/>
            <x-form.checkbox name="is_active" label="Plan activo (visible)" :checked="$plan->is_active"/>
        </div>
        <div class="sm:col-span-3"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar plan</button></div>
    </form>
</x-admin-layout>
