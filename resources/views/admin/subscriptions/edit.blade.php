<x-admin-layout title="Editar suscripción">
    <x-page-header :title="'Suscripción de '.$subscription->company?->name" :back="route('admin.subscriptions.index')"/>
    <form method="POST" action="{{ route('admin.subscriptions.update', $subscription) }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf @method('PUT')
        <x-form.select name="plan_id" label="Plan" :options="$plans->pluck('name', 'id')->all()" :value="$subscription->plan_id" required/>
        <x-form.select name="billing_cycle" label="Ciclo" :options="\App\Models\Subscription::CYCLES" :value="$subscription->billing_cycle"/>
        <x-form.select name="status" label="Estado" :options="collect(\App\Models\Subscription::STATUSES)->map(fn ($s) => $s[0])->all()" :value="$subscription->status"/>
        <x-form.input name="price" type="number" step="0.01" label="Precio" :value="$subscription->price" required/>
        <x-form.input name="trial_ends_at" type="date" label="Fin de la prueba" :value="$subscription->trial_ends_at"/>
        <x-form.input name="ends_at" type="date" label="Fin del periodo pagado" :value="$subscription->ends_at" hint="Vacío = sin vencimiento"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar</button></div>
    </form>
</x-admin-layout>
