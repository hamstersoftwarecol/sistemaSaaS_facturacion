{{-- Editor de líneas reutilizable (documentos y facturas recurrentes). Requiere el componente Alpine documentEditor. --}}
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Líneas del documento</h2>
        <span class="text-xs text-slate-400 hidden sm:block">Escriba para buscar por nombre, SKU o código de barras · Enter selecciona</span>
    </div>
    <div class="overflow-x-auto">
        <table class="table min-w-[900px]">
            <thead>
                <tr>
                    <th>Producto / concepto</th>
                    <th class="w-28">Cantidad</th>
                    <th class="w-32">Precio</th>
                    <th class="w-24">Desc. %</th>
                    <th class="w-48">Impuesto</th>
                    <th class="w-32 text-right">Total</th>
                    <th class="w-10"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <template x-for="(item, index) in items" :key="item.uid">
                    <tr class="align-top">
                        <td>
                            <div class="relative" @click.outside="item.open = false">
                                <div class="flex items-center gap-2">
                                    <input type="text" data-product-search class="form-input" autocomplete="off"
                                        :placeholder="item.product_id ? '📦 ' + productName(item.product_id) : 'Buscar producto o servicio...'"
                                        x-model="item.search" @focus="item.open = true" @input="item.open = true"
                                        @keydown.escape="item.open = false"
                                        @keydown.enter.prevent="filteredProducts(item)[0] && selectProduct(item, filteredProducts(item)[0])">
                                    <button type="button" x-show="item.product_id" @click="clearProduct(item)" class="text-slate-400 hover:text-slate-600" title="Quitar producto (concepto libre)">
                                        <x-icon name="x" class="h-4 w-4"/>
                                    </button>
                                </div>
                                <div x-show="item.open && item.search !== '' && filteredProducts(item).length" x-cloak
                                     class="absolute z-30 mt-1 max-h-72 w-[26rem] overflow-auto rounded-lg bg-white py-1 shadow-xl ring-1 ring-black/5">
                                    <template x-for="product in filteredProducts(item)" :key="product.id">
                                        <button type="button" @click="selectProduct(item, product)" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-indigo-50">
                                            <span class="min-w-0">
                                                <span class="block truncate font-medium text-slate-800" x-text="product.name"></span>
                                                <span class="block text-xs text-slate-400" x-text="product.sku + (product.barcode ? ' · ' + product.barcode : '')"></span>
                                            </span>
                                            <span class="shrink-0 text-right">
                                                <span class="block font-medium text-slate-700" x-text="formatMoney(priceFor(product))"></span>
                                                <span class="block text-xs" :class="product.stock !== null && product.stock <= 0 ? 'text-rose-500' : 'text-slate-400'" x-show="product.stock !== null" x-text="'Stock: ' + product.stock"></span>
                                            </span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                            <input type="hidden" :name="`items[${index}][unit]`" :value="item.unit">
                            <input type="text" class="form-input mt-2" :name="`items[${index}][description]`" x-model="item.description" placeholder="Descripción del concepto">
                        </td>
                        <td><input type="number" step="any" min="0" class="form-input text-right" :name="`items[${index}][quantity]`" x-model.number="item.quantity"></td>
                        <td><input type="number" step="0.01" min="0" class="form-input text-right" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price"></td>
                        <td><input type="number" step="0.01" min="0" max="100" class="form-input text-right" :name="`items[${index}][discount_percent]`" x-model.number="item.discount_percent"></td>
                        <td>
                            <select class="form-select" :name="`items[${index}][tax_rate_id]`" x-model="item.tax_rate_id">
                                <option value="">Sin impuesto</option>
                                <template x-for="tax in taxRates" :key="tax.id">
                                    <option :value="String(tax.id)" x-text="tax.name" :selected="String(tax.id) === String(item.tax_rate_id)"></option>
                                </template>
                            </select>
                        </td>
                        <td class="pt-5 text-right font-medium whitespace-nowrap" x-text="formatMoney(lineTotal(item))"></td>
                        <td class="pt-4">
                            <button type="button" @click="removeItem(index)" class="text-slate-400 hover:text-rose-600" title="Eliminar línea"><x-icon name="trash" class="h-4 w-4"/></button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 p-4">
        <button type="button" @click="addItem()" class="btn-secondary btn-sm"><x-icon name="plus" class="h-4 w-4"/> Agregar línea</button>
    </div>
</div>
