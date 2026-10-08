<x-app-layout :title="$priceList->name">
    <x-page-header :title="$priceList->name" :subtitle="$priceList->description" :back="route('price-lists.index')">
        @can('price_lists.edit')<a href="{{ route('price-lists.edit', $priceList) }}" class="btn-primary"><x-icon name="pencil" class="h-4 w-4"/> Editar precios</a>@endcan
        @can('price_lists.delete')<x-delete-button :action="route('price-lists.destroy', $priceList)"/>@endcan
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>Producto</th><th>SKU</th><th class="text-right">Precio base</th><th class="text-right">Precio en lista</th><th class="text-right">Diferencia</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($products as $product)
                    @php $price = $priceList->priceFor($product); $fixed = $priceList->items->firstWhere('product_id', $product->id); @endphp
                    <tr>
                        <td>{{ $product->name }} @if ($fixed)<x-badge color="indigo">fijo</x-badge>@endif</td>
                        <td class="text-slate-500">{{ $product->sku }}</td>
                        <td class="text-right">{{ money($product->sale_price) }}</td>
                        <td class="text-right font-semibold">{{ money($price) }}</td>
                        <td class="text-right {{ $price < $product->sale_price ? 'text-emerald-600' : 'text-slate-500' }}">{{ $product->sale_price > 0 ? num(($price - $product->sale_price) / $product->sale_price * 100, 1).'%' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-slate-100 p-4">{{ $products->links() }}</div>
    </div>
</x-app-layout>
