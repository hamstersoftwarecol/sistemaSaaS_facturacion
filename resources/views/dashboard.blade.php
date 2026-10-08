<x-app-layout title="Panel de control">
    @if ($limited)
        <x-page-header :title="'Hola, '.auth()->user()->name" subtitle="Bienvenido al sistema de facturación"/>
        <div class="card card-body text-sm text-slate-600">Su rol no tiene acceso a los indicadores del panel. Use el menú lateral para acceder a sus módulos.</div>
    @else
        <x-page-header :title="'Hola, '.explode(' ', auth()->user()->name)[0].' 👋'" :subtitle="'Resumen de '.(company()->name).' · '.now()->locale('es')->translatedFormat('l j \d\e F \d\e Y')">
            @can('reports.view')<a href="{{ route('reports.index') }}" class="btn-secondary"><x-icon name="chart" class="h-4 w-4"/> Informes</a>@endcan
        </x-page-header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat label="Ventas del mes" :value="money($salesMonth)" icon="trending-up" color="indigo"
                :hint="$salesGrowth === null ? 'Sin datos del mes anterior' : new \Illuminate\Support\HtmlString('<span class=\''.($salesGrowth >= 0 ? 'text-emerald-600' : 'text-rose-600').' font-semibold\'>'.($salesGrowth >= 0 ? '▲ ' : '▼ ').num(abs($salesGrowth), 1).'%</span> vs. mes anterior')"/>
            <x-stat label="Cobrado este mes" :value="money($collectedMonth)" icon="banknotes" color="green"/>
            <x-stat label="Cuentas por cobrar" :value="money($receivables)" icon="clock" color="yellow" :hint="new \Illuminate\Support\HtmlString('<span class=\'text-rose-600 font-semibold\'>'.e(money($overdue)).'</span> vencido')"/>
            <x-stat label="Disponible en caja y bancos" :value="money($cashBalance)" icon="bank" color="blue"/>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="card self-start xl:col-span-2">
                <div class="card-header"><h2 class="card-title">Ventas, cobros y gastos (12 meses)</h2></div>
                <div class="card-body"><canvas id="mainChart" height="110"></canvas></div>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-stat label="Gastos del mes" :value="money($expensesMonth)" class="!p-4"/>
                    <x-stat label="Por pagar" :value="money($payables)" class="!p-4"/>
                </div>
                <div class="card card-body">
                    <h2 class="card-title mb-3">Pendientes</h2>
                    <ul class="space-y-2.5 text-sm">
                        <li class="flex justify-between"><a href="{{ route('quotes.index', ['status' => 'sent']) }}" class="text-slate-600 hover:text-indigo-600">Presupuestos abiertos</a><x-badge color="blue">{{ $pendingQuotes }}</x-badge></li>
                        <li class="flex justify-between"><a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="text-slate-600 hover:text-indigo-600">Facturas vencidas</a><x-badge color="red">{{ $overdueInvoices->count() }}</x-badge></li>
                        <li class="flex justify-between"><a href="{{ route('cheques.index') }}" class="text-slate-600 hover:text-indigo-600">Cheques a 7 días</a><x-badge color="yellow">{{ $chequesDue }}</x-badge></li>
                        <li class="flex justify-between"><a href="{{ route('loans.index') }}" class="text-slate-600 hover:text-indigo-600">Cuotas de préstamos a 7 días</a><x-badge color="yellow">{{ $installmentsDue }}</x-badge></li>
                        <li class="flex justify-between"><a href="{{ route('inventory.index', ['low_stock' => 1]) }}" class="text-slate-600 hover:text-indigo-600">Productos con stock bajo</a><x-badge color="red">{{ $lowStock->count() }}</x-badge></li>
                    </ul>
                </div>
                <div class="card card-body">
                    <h2 class="card-title mb-3">Estado de facturas</h2>
                    <div class="mx-auto max-w-[220px]"><canvas id="statusChart"></canvas></div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="card self-start xl:col-span-2">
                <div class="card-header"><h2 class="card-title">Últimas facturas</h2><a href="{{ route('invoices.index') }}" class="text-sm link">Ver todas</a></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Número</th><th>Cliente</th><th>Fecha</th><th class="text-right">Total</th><th>Estado</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentInvoices as $inv)
                                <tr><td><a class="link" href="{{ route('invoices.show', $inv) }}">{{ $inv->number }}</a></td><td>{{ $inv->customer?->name }}</td><td>{{ fdate($inv->date) }}</td><td class="text-right font-medium">{{ money($inv->total) }}</td><td><x-badge :color="$inv->statusColor()">{{ $inv->statusLabel() }}</x-badge></td></tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-slate-500">Aún no hay facturas. <a href="{{ route('invoices.create') }}" class="link">Cree la primera</a>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="space-y-6">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Mejores clientes (12 meses)</h2></div>
                    <ul class="divide-y divide-slate-100 text-sm">
                        @forelse ($topCustomers as $row)
                            <li class="flex items-center justify-between px-5 py-3"><span class="truncate">{{ $row->customer?->name }}</span><span class="font-semibold">{{ money($row->total) }}</span></li>
                        @empty
                            <li class="px-5 py-4 text-slate-500">Sin datos.</li>
                        @endforelse
                    </ul>
                </div>
                @if ($overdueInvoices->isNotEmpty())
                    <div class="card">
                        <div class="card-header"><h2 class="card-title text-rose-600">Facturas vencidas</h2></div>
                        <ul class="divide-y divide-slate-100 text-sm">
                            @foreach ($overdueInvoices as $inv)
                                <li class="flex items-center justify-between px-5 py-3">
                                    <div><a class="link" href="{{ route('invoices.show', $inv) }}">{{ $inv->number }}</a><p class="text-xs text-slate-400">{{ $inv->customer?->name }} · {{ (int) $inv->due_date->diffInDays(today()) }} días</p></div>
                                    <span class="font-semibold text-rose-600">{{ money($inv->balance) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if ($lowStock->isNotEmpty())
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Stock bajo</h2></div>
                        <ul class="divide-y divide-slate-100 text-sm">
                            @foreach ($lowStock as $p)
                                <li class="flex justify-between px-5 py-2.5"><a href="{{ route('products.show', $p) }}" class="truncate hover:text-indigo-600">{{ $p->name }}</a><span class="font-semibold text-rose-600">{{ qty($p->currentStock()) }} / {{ qty($p->min_stock) }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const chart = @js($chart);
                    new Chart(document.getElementById('mainChart'), {
                        type: 'bar',
                        data: {
                            labels: chart.labels,
                            datasets: [
                                { label: 'Ventas', data: chart.sales, backgroundColor: '#6366f1', borderRadius: 6 },
                                { label: 'Cobros', data: chart.collected, backgroundColor: '#10b981', borderRadius: 6 },
                                { label: 'Gastos', type: 'line', data: chart.expenses, borderColor: '#f43f5e', backgroundColor: '#f43f5e', tension: .35, pointRadius: 3 },
                            ],
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + formatMoney(c.raw) } } }, scales: { y: { ticks: { callback: (v) => formatMoney(v).replace(',00', '') } } } },
                    });
                    const status = @js($statusBreakdown->map(fn ($r) => (float) $r->total));
                    const labels = { unpaid: 'Pendientes', partial: 'Pago parcial', paid: 'Pagadas' };
                    const keys = Object.keys(labels).filter(k => status[k]);
                    new Chart(document.getElementById('statusChart'), {
                        type: 'doughnut',
                        data: { labels: keys.map(k => labels[k]), datasets: [{ data: keys.map(k => status[k]), backgroundColor: keys.map(k => ({ unpaid: '#f59e0b', partial: '#0ea5e9', paid: '#10b981' })[k]) }] },
                        options: { plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: (c) => c.label + ': ' + formatMoney(c.raw) } } }, cutout: '65%' },
                    });
                });
            </script>
        @endpush
    @endif
</x-app-layout>
