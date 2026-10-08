{{-- Cabecera común de informes: filtros, exportación e impresión --}}
<x-page-header :title="$definition['title']" :subtitle="($definition['dated'] ? 'Del '.fdate($from).' al '.fdate($to) : 'Situación al '.fdate(today())).(company() ? ' · '.company()->name : '')" :back="route('reports.index')">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondary no-print"><x-icon name="download" class="h-4 w-4"/> Exportar CSV</a>
    <button onclick="window.print()" class="btn-secondary no-print"><x-icon name="printer" class="h-4 w-4"/> Imprimir</button>
</x-page-header>
@if ($definition['dated'])
    <form method="GET" class="card no-print mb-6 flex flex-wrap items-end gap-3 p-4" x-data>
        <div><label class="form-label">Desde</label><input type="date" name="from" value="{{ $from }}" class="form-input" x-ref="from"></div>
        <div><label class="form-label">Hasta</label><input type="date" name="to" value="{{ $to }}" class="form-input" x-ref="to"></div>
        <button class="btn-primary">Aplicar</button>
        <div class="flex flex-wrap gap-1.5">
            @foreach ([
                'Este mes' => [now()->startOfMonth(), now()],
                'Mes anterior' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
                'Trimestre' => [now()->startOfQuarter(), now()],
                'Este año' => [now()->startOfYear(), now()],
                'Últimos 12 meses' => [now()->subMonths(12)->startOfMonth(), now()],
            ] as $label => [$a, $b])
                <a href="{{ request()->fullUrlWithQuery(['from' => $a->toDateString(), 'to' => $b->toDateString()]) }}" class="btn-ghost btn-sm">{{ $label }}</a>
            @endforeach
        </div>
    </form>
@endif
