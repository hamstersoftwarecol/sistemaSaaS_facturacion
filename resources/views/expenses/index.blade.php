<x-app-layout title="Gastos">
    <x-page-header title="Gastos" subtitle="Registre los gastos operativos de la empresa">
        @can('expenses.create')<a href="{{ route('expenses.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo gasto</a>@endcan
    </x-page-header>
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="min-w-[200px] flex-1"><label class="form-label">Buscar</label><input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Descripción o referencia"></div>
            <div><label class="form-label">Categoría</label><select name="category_id" class="form-select w-44"><option value="">Todas</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="form-label">Desde</label><input type="date" name="from" value="{{ request('from') }}" class="form-input"></div>
            <div><label class="form-label">Hasta</label><input type="date" name="to" value="{{ request('to') }}" class="form-input"></div>
            <button class="btn-secondary">Filtrar</button>
            <div class="ml-auto text-right"><p class="text-xs text-slate-500">Total</p><p class="text-lg font-semibold text-rose-600">{{ money($total) }}</p></div>
        </form>
        @if ($expenses->isEmpty())
            <x-empty-state icon="trending-down" title="Sin gastos" description="Registre alquileres, servicios, nómina y demás gastos."/>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Descripción</th><th>Categoría</th><th>Proveedor</th><th>Cuenta</th><th class="text-right">Impuesto</th><th class="text-right">Importe</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($expenses as $expense)
                            <tr>
                                <td class="whitespace-nowrap">{{ fdate($expense->date) }}</td>
                                <td>{{ $expense->description }}@if ($expense->reference)<div class="text-xs text-slate-400">{{ $expense->reference }}</div>@endif</td>
                                <td>@if ($expense->category)<x-badge color="purple">{{ $expense->category->name }}</x-badge>@endif</td>
                                <td>{{ $expense->supplier?->name ?? '—' }}</td>
                                <td>{{ $expense->account?->name }}</td>
                                <td class="text-right text-slate-500">{{ money($expense->tax_amount) }}</td>
                                <td class="text-right font-semibold">{{ money($expense->amount) }}</td>
                                <td class="text-right whitespace-nowrap">
                                    @can('expenses.edit')<a href="{{ route('expenses.edit', $expense) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a>@endcan
                                    @can('expenses.delete')<x-delete-button :action="route('expenses.destroy', $expense)" :small="true"/>@endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $expenses->links() }}</div>
        @endif
    </div>
</x-app-layout>
