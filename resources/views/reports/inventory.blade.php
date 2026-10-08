<x-app-layout title="Inventario valorizado">
    @include('reports._layout')
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Valor al costo" :value="money($data['costValue'])" icon="archive"/>
        <x-stat label="Valor a precio de venta" :value="money($data['saleValue'])" icon="tag" color="green"/>
        <x-stat label="Unidades" :value="qty($data['units'])" icon="cube" color="blue" :hint="$data['branch'] ? 'Sucursal: '.$data['branch']->name : 'Todas las sucursales'"/>
        <x-stat label="Bajo mínimo" :value="$data['lowCount']" icon="warning" color="red"/>
    </div>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>SKU</th><th>Producto</th><th>Categoría</th><th class="text-right">Existencias</th><th class="text-right">Mínimo</th><th class="text-right">Costo unit.</th><th class="text-right">Valor costo</th><th class="text-right">Valor venta</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($data['rows'] as $row)
                    <tr class="{{ $row['low'] ? 'bg-rose-50/40' : '' }}">
                        <td class="text-slate-500">{{ $row['product']->sku }}</td>
                        <td>{{ $row['product']->name }}</td>
                        <td>{{ $row['product']->category?->name ?? '—' }}</td>
                        <td class="text-right font-medium {{ $row['low'] ? 'text-rose-600' : '' }}">{{ qty($row['stock']) }}</td>
                        <td class="text-right text-slate-400">{{ qty($row['product']->min_stock) }}</td>
                        <td class="text-right">{{ money($row['product']->cost_price) }}</td>
                        <td class="text-right">{{ money($row['cost_value']) }}</td>
                        <td class="text-right">{{ money($row['sale_value']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="6">Totales</td><td class="text-right">{{ money($data['costValue']) }}</td><td class="text-right">{{ money($data['saleValue']) }}</td></tr></tfoot>
        </table>
    </div>
</x-app-layout>
