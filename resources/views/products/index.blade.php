<x-app-layout title="Productos">
    <x-page-header title="Productos y servicios" subtitle="Catálogo, precios y existencias">
        <a href="{{ route('labels.index') }}" class="btn-secondary"><x-icon name="barcode" class="h-4 w-4"/> Etiquetas</a>
        @can('products.create')<a href="{{ route('products.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo producto</a>@endcan
    </x-page-header>
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="min-w-[220px] flex-1"><label class="form-label">Buscar</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Nombre, SKU o código de barras" class="form-input"></div>
            <div><label class="form-label">Categoría</label>
                <select name="category_id" class="form-select w-44"><option value="">Todas</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div><label class="form-label">Tipo</label>
                <select name="type" class="form-select w-36"><option value="">Todos</option>
                    @foreach (\App\Models\Product::TYPES as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <label class="inline-flex items-center gap-2 pb-2 text-sm"><input type="checkbox" name="low_stock" value="1" class="form-checkbox" @checked(request('low_stock'))> Stock bajo</label>
            <button class="btn-secondary"><x-icon name="search" class="h-4 w-4"/> Filtrar</button>
        </form>
        @if ($products->isEmpty())
            <x-empty-state icon="cube" title="Sin productos" description="Agregue productos o servicios para incluirlos en sus documentos."/>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Producto</th><th>SKU / Código</th><th>Categoría</th><th class="text-right">Costo</th><th class="text-right">Precio</th><th>Impuesto</th><th class="text-right">Stock</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            @php $stock = $product->currentStock(); @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $product->name }}</a>
                                    <div class="text-xs text-slate-400">{{ \App\Models\Product::TYPES[$product->type] }}{{ $product->is_active ? '' : ' · Inactivo' }}</div>
                                </td>
                                <td class="text-slate-500"><div>{{ $product->sku }}</div><div class="text-xs">{{ $product->barcode }}</div></td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td class="text-right text-slate-500">{{ money($product->cost_price) }}</td>
                                <td class="text-right font-medium">{{ money($product->sale_price) }}</td>
                                <td>{{ $product->taxRate?->label() ?? '—' }}</td>
                                <td class="text-right">
                                    @if ($product->track_stock)
                                        <span class="{{ $stock <= (float) $product->min_stock ? 'font-semibold text-rose-600' : '' }}">{{ qty($stock) }}</span> <span class="text-xs text-slate-400">{{ $product->unit }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="text-right">@can('products.edit')<a href="{{ route('products.edit', $product) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $products->links() }}</div>
        @endif
    </div>
</x-app-layout>
