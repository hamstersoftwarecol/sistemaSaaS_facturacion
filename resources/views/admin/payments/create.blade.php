<x-admin-layout title="Registrar pago">
    <x-page-header title="Registrar pago manual" subtitle="El pago se aprobará y la suscripción se activará o renovará automáticamente" :back="route('admin.payments.index')"/>
    <form method="POST" action="{{ route('admin.payments.store') }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        <x-form.select name="company_id" label="Empresa" :options="$companies->pluck('name', 'id')->all()" :value="$companyId" placeholder="Seleccione…" required/>
        <x-form.select name="plan_id" label="Plan" :options="$plans->pluck('name', 'id')->all()" required/>
        <x-form.select name="billing_cycle" label="Ciclo" :options="\App\Models\Subscription::CYCLES"/>
        <x-form.input name="amount" type="number" step="0.01" label="Importe" required/>
        <x-form.select name="payment_method_id" label="Método" :options="$methods->pluck('name', 'id')->all()" placeholder="Manual"/>
        <x-form.input name="reference" label="Referencia"/>
        <x-form.textarea name="notes" label="Notas" class="sm:col-span-2" rows="2"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Registrar y activar</button></div>
    </form>
</x-admin-layout>
