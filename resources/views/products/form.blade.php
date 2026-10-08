<x-app-layout :title="$product->exists ? 'Editar producto' : 'Nuevo producto'">
    <x-page-header :title="$product->exists ? 'Editar '.$product->name : 'Nuevo producto o servicio'" :back="$product->exists ? route('products.show', $product) : route('products.index')"/>
    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="max-w-4xl space-y-6"
          x-data="{ type: @js(old('type', $product->type)), cost: @js((float) old('cost_price', $product->cost_price)), price: @js((float) old('sale_price', $product->sale_price)),
                    get margin() { return this.price > 0 ? ((this.price - this.cost) / this.price * 100).toFixed(1) : '0.0' } }">
        @csrf
        @if ($product->exists) @method('PUT') @endif
        <div class="card">
            <div class="card-header"><h2 class="card-title">Información</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select" x-model="type">
                        @foreach (\App\Models\Product::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select>
                </div>
                <x-form.select name="category_id" label="Categoría" :options="$categories->pluck('name', 'id')->all()" :value="$product->category_id" placeholder="Sin categoría"/>
                <x-form.input name="name" label="Nombre" :value="$product->name" required class="sm:col-span-2"/>
                <x-form.input name="sku" label="Código / SKU" :value="$product->sku" required/>
                <x-form.input name="barcode" label="Código de barras (EAN/UPC)" :value="$product->barcode" hint="Si se deja vacío, las etiquetas usarán el SKU."/>
                <x-form.textarea name="description" label="Descripción" :value="$product->description" class="sm:col-span-2" rows="2"/>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Precios e impuestos</h2><span class="text-xs text-slate-500">Margen: <strong x-text="margin + '%'"></strong></span></div>
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-4">
                <div><label class="form-label">Precio de costo *</label><input type="number" step="0.01" min="0" name="cost_price" x-model.number="cost" class="form-input" required></div>
                <div><label class="form-label">Precio de venta *</label><input type="number" step="0.01" min="0" name="sale_price" x-model.number="price" class="form-input" required></div>
                <x-form.select name="tax_rate_id" label="Impuesto" :options="$taxRates->mapWithKeys(fn ($t) => [$t->id => $t->label()])->all()" :value="$product->tax_rate_id" placeholder="Sin impuesto"/>
                <x-form.select name="unit" label="Unidad" :options="\App\Models\Product::UNITS" :value="$product->unit" required/>
            </div>
        </div>
        <div class="card" x-show="type === 'product'">
            <div class="card-header"><h2 class="card-title">Inventario</h2></div>
            <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-form.checkbox name="track_stock" label="Controlar existencias" :checked="$product->track_stock" hint="Descuenta stock al facturar y lo suma al recibir compras."/>
                <x-form.input name="min_stock" type="number" step="any" label="Stock mínimo (alerta)" :value="$product->min_stock"/>
                @unless ($product->exists)
                    <div class="sm:col-span-2">
                        <p class="form-label">Inventario inicial por sucursal</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            @foreach ($branches as $branch)
                                <label class="block text-xs text-slate-500">{{ $branch->name }}
                                    <input type="number" step="any" name="opening_stock[{{ $branch->id }}]" value="{{ old('opening_stock.'.$branch->id) }}" class="form-input mt-1" placeholder="0">
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endunless
            </div>
        </div>
        @if ($customFields->isNotEmpty())
            <div class="card card-body"><x-custom-fields :fields="$customFields" :values="$product->custom_fields ?? []"/></div>
        @endif
        <div class="flex items-center gap-4">
            <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar producto</button>
            <x-form.checkbox name="is_active" label="Activo" :checked="$product->is_active"/>
        </div>
    </form>
</x-app-layout>
