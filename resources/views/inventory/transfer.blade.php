@php $productData = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name.' ('.$p->sku.')', 'stocks' => $p->stocks->pluck('quantity', 'branch_id')->map(fn ($q) => (float) $q)])->values(); @endphp
<x-app-layout title="Transferencia entre sucursales">
    <x-page-header title="Transferencia entre sucursales" subtitle="Mueva mercancía de una sucursal a otra" :back="route('inventory.index')"/>
    @if ($branches->count() < 2)
        <div class="card"><x-empty-state icon="map-pin" title="Necesita al menos dos sucursales" description="Cree otra sucursal en Configuración para poder transferir mercancía."/></div>
    @else
        <form method="POST" action="{{ route('inventory.transfer.store') }}" class="max-w-4xl space-y-6"
              x-data="{ from: @js((string) old('from_branch_id', $branches->first()->id)), to: @js((string) old('to_branch_id', $branches->skip(1)->first()->id)), products: @js($productData), lines: [{ product_id: '', quantity: '' }],
                        stock(id, b) { const p = this.products.find(p => String(p.id) === String(id)); return p ? (p.stocks[b] ?? 0) : 0 } }">
            @csrf
            <div class="card card-body grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div><label class="form-label">Desde</label><select name="from_branch_id" x-model="from" class="form-select">@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                <div><label class="form-label">Hacia</label><select name="to_branch_id" x-model="to" class="form-select">@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                <x-form.input name="notes" label="Observaciones"/>
            </div>
            <div class="card">
                <table class="table">
                    <thead><tr><th>Producto</th><th class="w-32 text-right">Disponible origen</th><th class="w-40">Cantidad</th><th class="w-10"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(line, i) in lines" :key="i">
                            <tr>
                                <td><select class="form-select" :name="`lines[${i}][product_id]`" x-model="line.product_id" required>
                                    <option value="">Seleccione…</option>
                                    <template x-for="p in products" :key="p.id"><option :value="String(p.id)" x-text="p.name"></option></template>
                                </select></td>
                                <td class="text-right text-slate-500" x-text="line.product_id ? stock(line.product_id, from) : '—'"></td>
                                <td><input type="number" step="any" min="0" class="form-input text-right" :name="`lines[${i}][quantity]`" x-model="line.quantity" required></td>
                                <td><button type="button" class="text-slate-400 hover:text-rose-600" @click="lines.splice(i, 1); if (!lines.length) lines.push({product_id: '', quantity: ''})"><x-icon name="trash" class="h-4 w-4"/></button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="border-t border-slate-100 p-4"><button type="button" class="btn-secondary btn-sm" @click="lines.push({ product_id: '', quantity: '' })"><x-icon name="plus" class="h-4 w-4"/> Agregar producto</button></div>
            </div>
            <button class="btn-primary"><x-icon name="switch" class="h-4 w-4"/> Transferir</button>
        </form>
    @endif
</x-app-layout>
