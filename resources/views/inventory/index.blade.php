<x-app-layout title="Existencias">
    <x-page-header title="Inventario" subtitle="Existencias por sucursal, valorización y alertas">
        <a href="{{ route('inventory.movements') }}" class="btn-secondary"><x-icon name="list" class="h-4 w-4"/> Movimientos</a>
        @can('inventory.create')
            <a href="{{ route('inventory.transfer') }}" class="btn-secondary"><x-icon name="switch" class="h-4 w-4"/> Transferir</a>
            <a href="{{ route('inventory.adjust') }}" class="btn-primary"><x-icon name="adjustments" class="h-4 w-4"/> Ajuste de inventario</a>
        @endcan
    </x-page-header>
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Valor al costo" :value="money($valuation)" icon="archive"/>
        <x-stat label="Valor a precio de venta" :value="money($saleValue)" icon="tag" color="green"/>
        <x-stat label="Productos con stock bajo" :value="$lowCount" icon="warning" color="red"/>
    </div>
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="min-w-[220px] flex-1"><label class="form-label">Buscar</label><input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Producto, SKU o código"></div>
            <div><label class="form-label">Categoría</label>
                <select name="category_id" class="form-select w-44"><option value="">Todas</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <label class="inline-flex items-center gap-2 pb-2 text-sm"><input type="checkbox" name="low_stock" value="1" class="form-checkbox" @checked(request('low_stock'))> Solo stock bajo</label>
            <button class="btn-secondary">Filtrar</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Producto</th>
                        @foreach ($branches as $branch)<th class="text-right">{{ $branch->name }}</th>@endforeach
                        <th class="text-right">Total</th><th class="text-right">Mínimo</th><th class="text-right">Valor</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        @php $total = $product->stockIn(); @endphp
                        <tr>
                            <td><a href="{{ route('products.show', $product) }}" class="font-medium hover:text-indigo-600">{{ $product->name }}</a><div class="text-xs text-slate-400">{{ $product->sku }}</div></td>
                            @foreach ($branches as $branch)<td class="text-right">{{ qty($product->stockIn($branch->id)) }}</td>@endforeach
                            <td class="text-right font-semibold {{ $total <= (float) $product->min_stock ? 'text-rose-600' : '' }}">{{ qty($total) }}</td>
                            <td class="text-right text-slate-400">{{ qty($product->min_stock) }}</td>
                            <td class="text-right">{{ money($total * $product->cost_price) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $branches->count() + 4 }}" class="py-8 text-center text-slate-500">No hay productos con control de inventario.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $products->links() }}</div>
    </div>
</x-app-layout>
