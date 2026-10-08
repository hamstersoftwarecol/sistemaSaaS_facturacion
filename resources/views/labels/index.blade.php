<x-app-layout title="Etiquetas de código de barras">
    <x-page-header title="Etiquetas de código de barras" subtitle="Genere hojas de etiquetas Code128 listas para imprimir" :back="route('products.index')"/>
    <form method="POST" action="{{ route('labels.print') }}" target="_blank" class="space-y-6"
          x-data="{ filter: '', setAll(v) { document.querySelectorAll('[data-qty]').forEach(i => { if (i.closest('tr').style.display !== 'none') i.value = v }) } }">
        @csrf
        <div class="card card-body flex flex-wrap items-end gap-5">
            <div><label class="form-label">Columnas por fila</label>
                <select name="columns" class="form-select w-32">@foreach ([2, 3, 4, 5] as $c)<option value="{{ $c }}" @selected($c === 3)>{{ $c }}</option>@endforeach</select></div>
            <label class="inline-flex items-center gap-2 pb-2 text-sm"><input type="hidden" name="show_name" value="0"><input type="checkbox" name="show_name" value="1" checked class="form-checkbox"> Mostrar nombre</label>
            <label class="inline-flex items-center gap-2 pb-2 text-sm"><input type="hidden" name="show_price" value="0"><input type="checkbox" name="show_price" value="1" checked class="form-checkbox"> Mostrar precio</label>
            <div class="ml-auto flex gap-2">
                <button type="button" class="btn-secondary" @click="setAll(1)">1 de cada uno</button>
                <button type="button" class="btn-secondary" @click="setAll(0)">Limpiar</button>
                <button class="btn-primary"><x-icon name="printer" class="h-4 w-4"/> Generar etiquetas</button>
            </div>
        </div>
        @error('quantities')<p class="form-error">{{ $message }}</p>@enderror
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Productos</h2>
                <input type="search" x-model="filter" placeholder="Filtrar…" class="form-input w-64">
            </div>
            <div class="max-h-[560px] overflow-y-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>Producto</th><th>Código</th><th>Categoría</th><th class="text-right">Precio</th><th class="w-32">Cantidad</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr x-show="!filter || @js(mb_strtolower($product->name.' '.$product->sku.' '.$product->barcode)).includes(filter.toLowerCase())">
                                <td class="font-medium">{{ $product->name }}</td>
                                <td class="font-mono text-xs text-slate-500">{{ $product->barcodeValue() }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td class="text-right">{{ money($product->sale_price) }}</td>
                                <td><input data-qty type="number" min="0" max="500" name="quantities[{{ $product->id }}]" value="{{ in_array($product->id, $preselected) ? 10 : 0 }}" class="form-input text-right"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</x-app-layout>
