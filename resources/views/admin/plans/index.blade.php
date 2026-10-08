<x-admin-layout title="Planes">
    <x-page-header title="Planes de suscripción" subtitle="Precios y límites que se muestran en la página pública">
        <a href="{{ route('admin.plans.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo plan</a>
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Plan</th><th class="text-right">Mensual</th><th class="text-right">Anual</th><th>Límites (usuarios / sucursales / facturas / productos)</th><th class="text-center">Prueba</th><th class="text-center">Suscripciones</th><th>Estado</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($plans as $plan)
                    <tr>
                        <td><p class="font-medium">{{ $plan->name }} @if ($plan->is_featured)<x-badge color="indigo">Destacado</x-badge>@endif</p><p class="text-xs text-slate-400">{{ $plan->slug }}</p></td>
                        <td class="text-right">${{ number_format($plan->price_monthly, 2) }}</td>
                        <td class="text-right">${{ number_format($plan->price_yearly, 2) }}</td>
                        <td class="text-slate-500">{{ $plan->limitLabel($plan->max_users) }} / {{ $plan->limitLabel($plan->max_branches) }} / {{ $plan->limitLabel($plan->max_invoices) }} / {{ $plan->limitLabel($plan->max_products) }}</td>
                        <td class="text-center">{{ $plan->trial_days }} días</td>
                        <td class="text-center">{{ $plan->subscriptions_count }}</td>
                        <td><x-badge :color="$plan->is_active ? 'green' : 'gray'">{{ $plan->is_active ? 'Activo' : 'Inactivo' }}</x-badge></td>
                        <td class="text-right whitespace-nowrap"><a href="{{ route('admin.plans.edit', $plan) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a><x-delete-button :action="route('admin.plans.destroy', $plan)" :small="true"/></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
