<x-app-layout title="Suscripción">
    <x-page-header title="Suscripción" subtitle="Plan contratado, consumo y facturación de la plataforma"/>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-1">
            <div class="bg-gradient-to-br from-indigo-600 to-violet-600 p-6 text-white">
                <p class="text-sm opacity-80">Plan actual</p>
                <p class="mt-1 text-3xl font-bold">{{ $subscription?->plan?->name ?? 'Sin plan' }}</p>
                @if ($subscription)
                    <span class="mt-3 inline-flex rounded-full bg-white/20 px-3 py-0.5 text-xs font-medium">{{ $subscription->statusLabel() }}</span>
                @endif
            </div>
            @if ($subscription)
                <dl class="card-body space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Ciclo</dt><dd>{{ $subscription->cycleLabel() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Precio</dt><dd>${{ number_format($subscription->price, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">{{ $subscription->status === 'trialing' ? 'Fin de la prueba' : 'Próxima renovación' }}</dt><dd>{{ $subscription->expiresAt() ? fdate($subscription->expiresAt()) : 'Sin vencimiento' }}</dd></div>
                    @if ($subscription->daysLeft() !== null)<div class="flex justify-between"><dt class="text-slate-500">Días restantes</dt><dd class="font-semibold">{{ $subscription->daysLeft() }}</dd></div>@endif
                </dl>
            @endif
        </div>
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Consumo del plan</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                @foreach ($usage as $row)
                    <div>
                        <div class="flex justify-between text-sm"><span class="text-slate-600">{{ $row['label'] }}</span><span class="font-medium">{{ $row['used'] }} / {{ $row['limit'] ?? '∞' }}</span></div>
                        <div class="mt-1.5 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $row['percent'] >= 90 ? 'bg-rose-500' : ($row['percent'] >= 70 ? 'bg-amber-500' : 'bg-indigo-500') }}" style="width: {{ $row['limit'] ? max(3, $row['percent']) : 3 }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <h2 class="mb-4 mt-10 text-lg font-semibold text-slate-900">Cambiar o renovar plan</h2>
    @cannot('subscription.edit')
        <p class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Solo el propietario o un usuario con permiso de suscripción puede cambiar el plan.</p>
    @endcannot
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4" x-data="{ cycle: 'monthly' }">
        <div class="md:col-span-2 xl:col-span-4 inline-flex w-fit rounded-lg bg-white p-1 ring-1 ring-slate-200">
            <button type="button" @click="cycle = 'monthly'" class="rounded-md px-4 py-1.5 text-sm font-medium" :class="cycle === 'monthly' ? 'bg-indigo-600 text-white' : 'text-slate-600'">Mensual</button>
            <button type="button" @click="cycle = 'yearly'" class="rounded-md px-4 py-1.5 text-sm font-medium" :class="cycle === 'yearly' ? 'bg-indigo-600 text-white' : 'text-slate-600'">Anual <span class="text-xs opacity-80">(ahorre)</span></button>
        </div>
        @foreach ($plans as $plan)
            @php $current = $subscription?->plan_id === $plan->id; @endphp
            <div class="card flex flex-col p-6 {{ $plan->is_featured ? 'ring-2 ring-indigo-500' : '' }}">
                <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ $plan->name }}</h3>@if ($current)<x-badge color="green">Actual</x-badge>@elseif ($plan->is_featured)<x-badge color="indigo">Popular</x-badge>@endif</div>
                <p class="mt-1 text-sm text-slate-500">{{ $plan->description }}</p>
                <p class="mt-4"><span class="text-3xl font-bold" x-text="cycle === 'monthly' ? '${{ number_format($plan->price_monthly, 0) }}' : '${{ number_format($plan->price_yearly, 0) }}'"></span>
                    <span class="text-sm text-slate-500" x-text="cycle === 'monthly' ? '/mes' : '/año'"></span></p>
                <ul class="mt-4 flex-1 space-y-1.5 text-sm text-slate-600">
                    @foreach ($plan->limitLines() as $line)
                        <li class="flex gap-2"><x-icon name="check" class="h-4 w-4 text-indigo-500"/>{{ $line }}</li>
                    @endforeach
                </ul>
                @can('subscription.edit')
                    <a :href="'{{ route('subscription.checkout', $plan) }}?cycle=' + cycle" class="{{ $plan->is_featured ? 'btn-primary' : 'btn-secondary' }} mt-5 w-full">{{ $current ? 'Renovar' : 'Elegir plan' }}</a>
                @endcan
            </div>
        @endforeach
    </div>

    <div class="card mt-10 overflow-x-auto">
        <div class="card-header"><h2 class="card-title">Historial de pagos de suscripción</h2></div>
        <table class="table">
            <thead><tr><th>Fecha</th><th>Plan</th><th>Ciclo</th><th>Método</th><th>Referencia</th><th class="text-right">Importe</th><th>Estado</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $p)
                    <tr><td>{{ fdate($p->created_at) }}</td><td>{{ $p->plan?->name }}</td><td>{{ \App\Models\Subscription::CYCLES[$p->billing_cycle] ?? '' }}</td><td>{{ $p->paymentMethod?->name ?? '—' }}</td><td>{{ $p->reference }}</td><td class="text-right">${{ number_format($p->amount, 2) }}</td><td><x-badge :color="$p->statusColor()">{{ $p->statusLabel() }}</x-badge></td></tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-slate-500">Sin pagos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
