<x-app-layout :title="$product->name">
    <x-page-header :title="$product->name" :subtitle="'SKU '.$product->sku.($product->category ? ' · '.$product->category->name : '')" :back="route('products.index')">
        <a href="{{ route('labels.index', ['products' => [$product->id]]) }}" class="btn-secondary"><x-icon name="barcode" class="h-4 w-4"/> Imprimir etiquetas</a>
        @can('inventory.create')<a href="{{ route('inventory.adjust') }}" class="btn-secondary"><x-icon name="adjustments" class="h-4 w-4"/> Ajustar stock</a>@endcan
        @can('products.edit')<a href="{{ route('products.edit', $product) }}" class="btn-primary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>@endcan
    </x-page-header>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Precio de venta" :value="money($product->sale_price)" icon="tag"/>
        <x-stat label="Costo" :value="money($product->cost_price)" icon="dollar" color="gray" :hint="'Margen '.($product->sale_price > 0 ? num(($product->sale_price - $product->cost_price) / $product->sale_price * 100, 1) : 0).'%'"/>
        <x-stat label="Existencias totales" :value="$product->track_stock ? qty($product->stockIn()).' '.$product->unit : 'No controla'" icon="archive" :color="$product->isLowStock() ? 'red' : 'green'"/>
        <x-stat label="Unidades vendidas" :value="qty($sold)" icon="chart" color="purple"/>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @if ($product->track_stock)
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Existencias por sucursal</h2></div>
                    <table class="table">
                        <thead><tr><th>Sucursal</th><th class="text-right">Cantidad</th><th class="text-right">Valor al costo</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($branches as $branch)
                                @php $q = $product->stockIn($branch->id); @endphp
                                <tr><td>{{ $branch->name }}</td><td class="text-right {{ $q <= (float) $product->min_stock ? 'text-rose-600 font-semibold' : '' }}">{{ qty($q) }} {{ $product->unit }}</td><td class="text-right">{{ money($q * $product->cost_price) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <div class="card">
                <div class="card-header"><h2 class="card-title">Últimos movimientos</h2><a href="{{ route('inventory.movements', ['product_id' => $product->id]) }}" class="text-sm link">Ver todos</a></div>
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Sucursal</th><th>Referencia</th><th class="text-right">Cantidad</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($movements as $mv)
                            <tr>
                                <td>{{ fdate($mv->date) }}</td>
                                <td><x-badge :color="$mv->typeColor()">{{ $mv->typeLabel() }}</x-badge></td>
                                <td>{{ $mv->branch?->name }}</td>
                                <td class="text-slate-500">{{ $mv->reference ?? $mv->notes }}</td>
                                <td class="text-right font-medium {{ $mv->quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $mv->quantity > 0 ? '+' : '' }}{{ qty($mv->quantity) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">Sin movimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="space-y-6">
            <div class="card card-body text-center">
                <h2 class="card-title mb-3 text-left">Código de barras</h2>
                <div class="flex justify-center">{!! $barcode !!}</div>
                <p class="mt-2 font-mono text-sm tracking-widest">{{ $product->barcodeValue() }}</p>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Precios por lista</h2></div>
                <ul class="divide-y divide-slate-100 text-sm">
                    <li class="flex justify-between px-5 py-2.5"><span>Precio base</span><span class="font-medium">{{ money($product->sale_price) }}</span></li>
                    @foreach ($priceLists as $list)
                        <li class="flex justify-between px-5 py-2.5"><span>{{ $list->name }}</span><span class="font-medium">{{ money($list->priceFor($product)) }}</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="card card-body text-sm">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-slate-500">Impuesto</dt><dd>{{ $product->taxRate?->label() ?? 'Sin impuesto' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Unidad</dt><dd>{{ \App\Models\Product::UNITS[$product->unit] ?? $product->unit }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Stock mínimo</dt><dd>{{ qty($product->min_stock) }}</dd></div>
                    @foreach ($customFields as $field)
                        @if (filled($product->custom_fields[$field->name] ?? null))
                            <div class="flex justify-between"><dt class="text-slate-500">{{ $field->label }}</dt><dd>{{ $field->displayValue($product->custom_fields[$field->name]) }}</dd></div>
                        @endif
                    @endforeach
                </dl>
                @if ($product->description)<p class="mt-3 text-slate-600">{{ $product->description }}</p>@endif
            </div>
            @can('products.delete')<x-delete-button :action="route('products.destroy', $product)" label="Eliminar producto" class="w-full"/>@endcan
        </div>
    </div>
</x-app-layout>
