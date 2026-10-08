<x-app-layout :title="$priceList->exists ? 'Editar lista' : 'Nueva lista de precios'">
    <x-page-header :title="$priceList->exists ? 'Editar '.$priceList->name : 'Nueva lista de precios'" :back="route('price-lists.index')"/>
    <form method="POST" action="{{ $priceList->exists ? route('price-lists.update', $priceList) : route('price-lists.store') }}" class="space-y-6"
          x-data="{ pct: @js((float) old('percentage', $priceList->percentage)), filter: '' }">
        @csrf
        @if ($priceList->exists) @method('PUT') @endif
        <div class="card card-body grid max-w-4xl grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Nombre" :value="$priceList->name" required/>
            <x-form.select name="type" label="Tipo" :options="\App\Models\PriceList::TYPES" :value="$priceList->type"/>
            <div>
                <label class="form-label">Ajuste porcentual sobre el precio base</label>
                <input type="number" step="0.01" name="percentage" x-model.number="pct" class="form-input">
                <p class="form-hint">Negativo = descuento (p. ej. -10), positivo = recargo. Se aplica a los productos sin precio fijo.</p>
            </div>
            <x-form.input name="description" label="Descripción" :value="$priceList->description"/>
            <x-form.checkbox name="is_active" label="Lista activa" :checked="$priceList->is_active"/>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Precios fijos por producto <span class="font-normal text-slate-400">(opcional)</span></h2>
                <input type="search" x-model="filter" placeholder="Filtrar productos…" class="form-input w-60">
            </div>
            <div class="max-h-[480px] overflow-y-auto">
                <table class="table">
                    <thead class="sticky top-0"><tr><th>Producto</th><th class="text-right">Precio base</th><th class="text-right">Con ajuste</th><th class="w-44 text-right">Precio fijo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($products as $product)
                            <tr x-show="!filter || @js(mb_strtolower($product->name.' '.$product->sku)).includes(filter.toLowerCase())">
                                <td>{{ $product->name }} <span class="text-xs text-slate-400">{{ $product->sku }}</span></td>
                                <td class="text-right text-slate-500">{{ money($product->sale_price) }}</td>
                                <td class="text-right" x-text="formatMoney({{ (float) $product->sale_price }} * (1 + pct / 100))"></td>
                                <td><input type="number" step="0.01" min="0" name="prices[{{ $product->id }}]" value="{{ old('prices.'.$product->id, $prices[$product->id] ?? '') }}" class="form-input text-right" placeholder="—"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar lista</button>
    </form>
</x-app-layout>
