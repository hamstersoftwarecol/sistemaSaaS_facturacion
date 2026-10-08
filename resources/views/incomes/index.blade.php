<x-app-layout title="Otros ingresos">
    <x-page-header title="Otros ingresos" subtitle="Ingresos distintos a las ventas: intereses, comisiones, alquileres…">
        @can('incomes.create')<a href="{{ route('incomes.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo ingreso</a>@endcan
    </x-page-header>
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="min-w-[200px] flex-1"><label class="form-label">Buscar</label><input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Descripción o referencia"></div>
            <div><label class="form-label">Categoría</label><select name="category_id" class="form-select w-44"><option value="">Todas</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="form-label">Desde</label><input type="date" name="from" value="{{ request('from') }}" class="form-input"></div>
            <div><label class="form-label">Hasta</label><input type="date" name="to" value="{{ request('to') }}" class="form-input"></div>
            <button class="btn-secondary">Filtrar</button>
            <div class="ml-auto text-right"><p class="text-xs text-slate-500">Total</p><p class="text-lg font-semibold text-emerald-600">{{ money($total) }}</p></div>
        </form>
        @if ($incomes->isEmpty())
            <x-empty-state icon="trending-up" title="Sin otros ingresos" description="Registre intereses, comisiones u otros ingresos no operativos."/>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Descripción</th><th>Categoría</th><th>Cliente</th><th>Cuenta</th><th class="text-right">Importe</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($incomes as $income)
                            <tr>
                                <td class="whitespace-nowrap">{{ fdate($income->date) }}</td>
                                <td>{{ $income->description }}@if ($income->reference)<div class="text-xs text-slate-400">{{ $income->reference }}</div>@endif</td>
                                <td>@if ($income->category)<x-badge color="purple">{{ $income->category->name }}</x-badge>@endif</td>
                                <td>{{ $income->customer?->name ?? '—' }}</td>
                                <td>{{ $income->account?->name }}</td>
                                
                                <td class="text-right font-semibold">{{ money($income->amount) }}</td>
                                <td class="text-right whitespace-nowrap">
                                    @can('incomes.edit')<a href="{{ route('incomes.edit', $income) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a>@endcan
                                    @can('incomes.delete')<x-delete-button :action="route('incomes.destroy', $income)" :small="true"/>@endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $incomes->links() }}</div>
        @endif
    </div>
</x-app-layout>
