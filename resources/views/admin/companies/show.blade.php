<x-admin-layout :title="$company->name">
    <x-page-header :title="$company->name" :subtitle="collect([$company->tax_id, $company->email, $company->country])->filter()->implode(' · ')" :back="route('admin.companies.index')">
        <form method="POST" action="{{ route('admin.companies.toggle', $company) }}">@csrf<button class="{{ $company->status === 'active' ? 'btn-secondary text-rose-600' : 'btn-success' }}">{{ $company->status === 'active' ? 'Suspender' : 'Reactivar' }}</button></form>
        <a href="{{ route('admin.payments.create', ['company_id' => $company->id]) }}" class="btn-secondary"><x-icon name="banknotes" class="h-4 w-4"/> Registrar pago</a>
        <a href="{{ route('admin.companies.edit', $company) }}" class="btn-primary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>
    </x-page-header>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Consumo</h2></div>
                <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                    @foreach ($usage as $row)
                        <div><div class="flex justify-between text-sm"><span>{{ $row['label'] }}</span><span class="font-medium">{{ $row['used'] }} / {{ $row['limit'] ?? '∞' }}</span></div>
                            <div class="mt-1.5 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-indigo-500" style="width: {{ $row['limit'] ? max(3, $row['percent']) : 3 }}%"></div></div></div>
                    @endforeach
                </div>
            </div>
            <div class="card overflow-x-auto">
                <div class="card-header"><h2 class="card-title">Usuarios</h2></div>
                <table class="table"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Último acceso</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">@foreach ($users as $u)<tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->roleName() }}</td><td class="text-slate-500">{{ $u->last_login_at?->diffForHumans() ?? 'Nunca' }}</td></tr>@endforeach</tbody></table>
            </div>
            <div class="card overflow-x-auto">
                <div class="card-header"><h2 class="card-title">Pagos de suscripción</h2></div>
                <table class="table"><thead><tr><th>Fecha</th><th>Plan</th><th>Método</th><th class="text-right">Importe</th><th>Estado</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">@forelse ($payments as $p)<tr><td>{{ fdate($p->created_at) }}</td><td>{{ $p->plan?->name }}</td><td>{{ $p->paymentMethod?->name ?? 'Manual' }}</td><td class="text-right">${{ number_format($p->amount, 2) }}</td><td><x-badge :color="$p->statusColor()">{{ $p->statusLabel() }}</x-badge></td></tr>@empty<tr><td colspan="5" class="py-6 text-center text-slate-500">Sin pagos.</td></tr>@endforelse</tbody></table>
            </div>
        </div>
        <div class="space-y-6">
            <div class="card card-body text-sm">
                <h2 class="card-title mb-3">Suscripción</h2>
                @if ($sub = $company->subscription)
                    <dl class="space-y-2">
                        <div class="flex justify-between"><dt class="text-slate-500">Plan</dt><dd class="font-semibold">{{ $sub->plan?->name }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Estado</dt><dd><x-badge :color="$sub->statusColor()">{{ $sub->statusLabel() }}</x-badge></dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Ciclo</dt><dd>{{ $sub->cycleLabel() }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Vence</dt><dd>{{ $sub->expiresAt() ? fdate($sub->expiresAt()) : 'Sin vencimiento' }}</dd></div>
                    </dl>
                    <a href="{{ route('admin.subscriptions.edit', $sub) }}" class="btn-secondary mt-4 w-full">Modificar suscripción</a>
                @else
                    <p class="text-slate-500">Sin suscripción.</p>
                @endif
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Sucursales</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">@foreach ($company->branches as $b)<li class="flex justify-between px-5 py-2.5"><span>{{ $b->name }}</span>@if ($b->is_main)<x-badge color="indigo">Principal</x-badge>@endif</li>@endforeach</ul>
            </div>
        </div>
    </div>
</x-admin-layout>
