<x-admin-layout title="Suscripciones">
    <x-page-header title="Suscripciones" subtitle="Estado de la suscripción de cada empresa"/>
    <div class="card">
        <form method="GET" class="flex flex-wrap gap-3 border-b border-slate-100 p-4">
            <select name="status" class="form-select w-48"><option value="">Todos los estados</option>@foreach (\App\Models\Subscription::STATUSES as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
            <select name="plan_id" class="form-select w-48"><option value="">Todos los planes</option>@foreach ($plans as $p)<option value="{{ $p->id }}" @selected(request('plan_id') == $p->id)>{{ $p->name }}</option>@endforeach</select>
            <button class="btn-secondary">Filtrar</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Empresa</th><th>Plan</th><th>Ciclo</th><th class="text-right">Precio</th><th>Inicio</th><th>Vence</th><th>Estado</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($subscriptions as $s)
                        <tr>
                            <td>@if ($s->company)<a href="{{ route('admin.companies.show', $s->company) }}" class="link">{{ $s->company->name }}</a>@endif</td>
                            <td>{{ $s->plan?->name }}</td>
                            <td>{{ $s->cycleLabel() }}</td>
                            <td class="text-right">${{ number_format($s->price, 2) }}</td>
                            <td>{{ fdate($s->starts_at) }}</td>
                            <td>{{ $s->expiresAt() ? fdate($s->expiresAt()) : '—' }}</td>
                            <td><x-badge :color="$s->statusColor()">{{ $s->statusLabel() }}</x-badge></td>
                            <td class="text-right"><a href="{{ route('admin.subscriptions.edit', $s) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $subscriptions->links() }}</div>
    </div>
</x-admin-layout>
