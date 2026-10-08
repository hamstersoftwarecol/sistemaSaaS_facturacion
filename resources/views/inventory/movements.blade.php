<x-app-layout title="Movimientos de inventario">
    <x-page-header title="Movimientos de inventario" subtitle="Kardex de entradas y salidas" :back="route('inventory.index')"/>
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div><label class="form-label">Producto</label>
                <select name="product_id" class="form-select w-64"><option value="">Todos</option>
                    @foreach ($products as $p)<option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="form-label">Tipo</label>
                <select name="type" class="form-select w-52"><option value="">Todos</option>
                    @foreach (\App\Models\StockMovement::TYPES as $k => [$l])<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div><label class="form-label">Desde</label><input type="date" name="from" value="{{ request('from') }}" class="form-input"></div>
            <div><label class="form-label">Hasta</label><input type="date" name="to" value="{{ request('to') }}" class="form-input"></div>
            <button class="btn-secondary">Filtrar</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Sucursal</th><th>Documento / Ref.</th><th>Usuario</th><th class="text-right">Cantidad</th><th class="text-right">Costo</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($movements as $mv)
                        <tr>
                            <td class="whitespace-nowrap">{{ fdate($mv->date) }}</td>
                            <td>{{ $mv->product?->name }}</td>
                            <td><x-badge :color="$mv->typeColor()">{{ $mv->typeLabel() }}</x-badge></td>
                            <td>{{ $mv->branch?->name }}</td>
                            <td>
                                @if ($mv->document)<a class="link" href="{{ route($mv->document->routeName('show'), $mv->document) }}">{{ $mv->document->number }}</a>@else<span class="text-slate-500">{{ $mv->reference }}</span>@endif
                                @if ($mv->notes)<div class="text-xs text-slate-400">{{ $mv->notes }}</div>@endif
                            </td>
                            <td class="text-slate-500">{{ $mv->user?->name }}</td>
                            <td class="text-right font-semibold {{ $mv->quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $mv->quantity > 0 ? '+' : '' }}{{ qty($mv->quantity) }}</td>
                            <td class="text-right text-slate-500">{{ money($mv->cost) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-center text-slate-500">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $movements->links() }}</div>
    </div>
</x-app-layout>
