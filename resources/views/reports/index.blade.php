<x-app-layout title="Informes">
    <x-page-header title="Informes" subtitle="Análisis de ventas, compras, impuestos, resultados y tesorería"/>
    @php $icons = ['chart' => 'chart', 'cart' => 'shopping-cart', 'percent' => 'receipt-tax', 'scale' => 'scale', 'inbox' => 'inbox', 'outbox' => 'send', 'box' => 'archive', 'cash' => 'banknotes', 'receipt' => 'trending-down', 'users' => 'users']; @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($reports as $slug => $report)
            <a href="{{ route('reports.show', $slug) }}" class="card group flex items-start gap-4 p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white">
                    <x-icon :name="$icons[$report['icon']] ?? 'chart'" class="h-5 w-5"/>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-900">{{ $report['title'] }}</h3>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $report['description'] }}</p>
                </div>
            </a>
        @endforeach
    </div>
</x-app-layout>
