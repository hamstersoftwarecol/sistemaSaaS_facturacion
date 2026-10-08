<x-admin-layout title="Resumen">
    <x-page-header title="Resumen de la plataforma" subtitle="Métricas del negocio SaaS">
        <a href="{{ route('admin.companies.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nueva empresa</a>
    </x-page-header>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="MRR (ingreso mensual recurrente)" :value="'$'.number_format($mrr, 2)" icon="refresh" color="indigo" :hint="'ARR: $'.number_format($arr, 2)"/>
        <x-stat label="Empresas" :value="$companies" icon="building" color="blue" :hint="$activeCompanies.' activas · '.$users.' usuarios'"/>
        <x-stat label="Suscripciones activas" :value="$activeSubscriptions" icon="badge-check" color="green" :hint="$trialing.' en prueba · '.$expired.' vencidas'"/>
        <x-stat label="Ingresos cobrados" :value="'$'.number_format($revenueTotal, 2)" icon="banknotes" color="purple" :hint="number_format($documents).' facturas emitidas en la plataforma'"/>
    </div>
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Ingresos y nuevas empresas (12 meses)</h2></div>
            <div class="card-body"><canvas id="adminChart" height="110"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Distribución por plan</h2></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($planDistribution as $plan)
                    <li class="flex items-center justify-between px-5 py-3"><span>{{ $plan->name }}</span><x-badge color="indigo">{{ $plan->subscriptions_count }}</x-badge></li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Pagos pendientes de aprobación</h2><a href="{{ route('admin.payments.index', ['status' => 'pending']) }}" class="text-sm link">Ver todos</a></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($pendingPayments as $p)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div><p class="font-medium">{{ $p->company?->name }}</p><p class="text-xs text-slate-500">{{ $p->plan?->name }} · {{ $p->paymentMethod?->name }} · Ref. {{ $p->reference }}</p></div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">${{ number_format($p->amount, 2) }}</span>
                            <form method="POST" action="{{ route('admin.payments.approve', $p) }}">@csrf<button class="btn-success btn-sm">Aprobar</button></form>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-slate-500">No hay pagos pendientes.</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Empresas recientes</h2><a href="{{ route('admin.companies.index') }}" class="text-sm link">Ver todas</a></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @foreach ($recentCompanies as $c)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div><a href="{{ route('admin.companies.show', $c) }}" class="font-medium hover:text-indigo-600">{{ $c->name }}</a><p class="text-xs text-slate-500">{{ $c->owner?->email }} · {{ $c->created_at->diffForHumans() }}</p></div>
                        @if ($c->subscription)<x-badge :color="$c->subscription->statusColor()">{{ $c->subscription->plan?->name }} · {{ $c->subscription->statusLabel() }}</x-badge>@endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const d = @js($chart);
                new Chart(document.getElementById('adminChart'), {
                    data: { labels: d.labels, datasets: [
                        { type: 'bar', label: 'Ingresos ($)', data: d.revenue, backgroundColor: '#6366f1', borderRadius: 6, yAxisID: 'y' },
                        { type: 'line', label: 'Nuevas empresas', data: d.signups, borderColor: '#10b981', backgroundColor: '#10b981', tension: .35, yAxisID: 'y1' },
                    ] },
                    options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { position: 'left' }, y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } } } },
                });
            });
        </script>
    @endpush
</x-admin-layout>
