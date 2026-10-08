<x-app-layout title="Informe de gastos">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card card-body xl:col-span-1">
            <p class="stat-label">Total de gastos</p>
            <p class="stat-value text-rose-600">{{ money($data['total']) }}</p>
            <div class="mt-4"><canvas id="expChart" height="220"></canvas></div>
        </div>
        <div class="card overflow-x-auto xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Por categoría</h2></div>
            <table class="table"><thead><tr><th>Categoría</th><th class="text-center">Registros</th><th class="text-right">Total</th><th class="text-right">%</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@forelse ($data['byCategory'] as $name => $row)<tr><td>{{ $name }}</td><td class="text-center">{{ $row['count'] }}</td><td class="text-right font-medium">{{ money($row['total']) }}</td><td class="text-right">{{ $data['total'] > 0 ? num($row['total'] / $data['total'] * 100, 1) : 0 }}%</td></tr>@empty<tr><td colspan="4" class="py-6 text-center text-slate-500">Sin gastos.</td></tr>@endforelse</tbody></table>
        </div>
    </div>
    <div class="card overflow-x-auto">
        <table class="table"><thead><tr><th>Fecha</th><th>Descripción</th><th>Categoría</th><th>Proveedor</th><th>Cuenta</th><th class="text-right">Importe</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@foreach ($data['expenses'] as $e)<tr><td>{{ fdate($e->date) }}</td><td>{{ $e->description }}</td><td>{{ $e->category?->name }}</td><td>{{ $e->supplier?->name }}</td><td>{{ $e->account?->name }}</td><td class="text-right">{{ money($e->amount) }}</td></tr>@endforeach</tbody></table>
    </div>
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded', () => new Chart(document.getElementById('expChart'), { type: 'doughnut', data: { labels: @js($data['byCategory']->keys()), datasets: [{ data: @js($data['byCategory']->pluck('total')->values()), backgroundColor: ['#6366f1', '#f43f5e', '#f59e0b', '#10b981', '#0ea5e9', '#8b5cf6', '#64748b', '#ec4899'] }] }, options: { plugins: { legend: { position: 'bottom' } } } }));</script>
    @endpush
</x-app-layout>
