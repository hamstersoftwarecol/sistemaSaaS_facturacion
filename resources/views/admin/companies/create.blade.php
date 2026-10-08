<x-admin-layout title="Nueva empresa">
    <x-page-header title="Nueva empresa" subtitle="Se crearán la sucursal principal, los roles, impuestos, categorías y la caja por defecto" :back="route('admin.companies.index')"/>
    <form method="POST" action="{{ route('admin.companies.store') }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        <x-form.input name="company_name" label="Nombre de la empresa" required class="sm:col-span-2"/>
        <x-form.input name="name" label="Nombre del propietario" required/>
        <x-form.input name="email" type="email" label="Correo del propietario" required/>
        <x-form.input name="password" type="password" label="Contraseña inicial" required/>
        <x-form.select name="plan_id" label="Plan" :options="$plans->pluck('name', 'id')->all()" required/>
        <x-form.select name="billing_cycle" label="Ciclo" :options="\App\Models\Subscription::CYCLES"/>
        <div class="grid grid-cols-2 gap-3"><x-form.input name="currency" label="Moneda" value="USD"/><x-form.input name="currency_symbol" label="Símbolo" value="$"/></div>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Crear empresa</button></div>
    </form>
</x-admin-layout>
