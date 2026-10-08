@php $productData = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name.' ('.$p->sku.')', 'stocks' => $p->stocks->pluck('quantity', 'branch_id')->map(fn ($q) => (float) $q)])->values(); @endphp
<x-app-layout title="Ajuste de inventario">
    <x-page-header title="Ajuste de inventario" subtitle="Corrija existencias tras un conteo físico, mermas o roturas" :back="route('inventory.index')"/>
    <form method="POST" action="{{ route('inventory.adjust.store') }}" class="max-w-4xl space-y-6"
          x-data="{ branch: @js((string) old('branch_id', $branchId)), mode: @js(old('mode', 'set')), products: @js($productData), lines: [{ product_id: '', quantity: '' }],
                    stock(id) { const p = this.products.find(p => String(p.id) === String(id)); return p ? (p.stocks[this.branch] ?? 0) : null } }">
        @csrf
        <div class="card card-body grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div><label class="form-label">Sucursal</label>
                <select name="branch_id" x-model="branch" class="form-select">@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div><label class="form-label">Modo</label>
                <select name="mode" x-model="mode" class="form-select"><option value="set">Fijar cantidad contada</option><option value="delta">Sumar / restar cantidad</option></select></div>
            <x-form.input name="notes" label="Motivo" placeholder="Conteo físico, merma, rotura…"/>
        </div>
        <div class="card">
            <table class="table">
                <thead><tr><th>Producto</th><th class="w-32 text-right">Stock actual</th><th class="w-44" x-text="mode === 'set' ? 'Cantidad contada' : 'Cantidad (+/-)'"></th><th class="w-10"></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(line, i) in lines" :key="i">
                        <tr>
                            <td><select class="form-select" :name="`lines[${i}][product_id]`" x-model="line.product_id" required>
                                <option value="">Seleccione…</option>
                                <template x-for="p in products" :key="p.id"><option :value="String(p.id)" x-text="p.name"></option></template>
                            </select></td>
                            <td class="text-right text-slate-500" x-text="line.product_id ? stock(line.product_id) : '—'"></td>
                            <td><input type="number" step="any" class="form-input text-right" :name="`lines[${i}][quantity]`" x-model="line.quantity" required></td>
                            <td><button type="button" class="text-slate-400 hover:text-rose-600" @click="lines.splice(i, 1); if (!lines.length) lines.push({product_id: '', quantity: ''})"><x-icon name="trash" class="h-4 w-4"/></button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="border-t border-slate-100 p-4"><button type="button" class="btn-secondary btn-sm" @click="lines.push({ product_id: '', quantity: '' })"><x-icon name="plus" class="h-4 w-4"/> Agregar producto</button></div>
        </div>
        <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Registrar ajuste</button>
    </form>
</x-app-layout>
