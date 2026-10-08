@php
    $quickConfig = [
        'lookupUrl' => route('products.lookup'),
        'storeUrl' => route('invoices.quick.store'),
        'defaultCustomerId' => $defaultCustomerId ? (string) $defaultCustomerId : '',
        'defaultAccountId' => $defaultAccountId ? (string) $defaultAccountId : '',
    ];
@endphp
<x-app-layout title="Venta rápida">
    <div x-data="quickInvoice(@js($quickConfig))" @keydown.window="onKey($event)" class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900"><x-icon name="bolt" class="h-6 w-6 text-amber-500"/> Venta rápida</h1>
                <p class="text-sm text-slate-500">Facture en segundos usando solo el teclado o un lector de código de barras.</p>
            </div>
            <div class="flex flex-wrap gap-1.5 text-xs text-slate-500">
                @foreach (['F2' => 'Buscar', 'F4' => 'Cliente', 'F8' => 'Pago', 'F9' => 'Cobrar', '↑↓' => 'Navegar', '+/-' => 'Cantidad', 'Supr' => 'Quitar', 'Esc' => 'Limpiar'] as $key => $label)
                    <span class="rounded-md bg-white px-2 py-1 ring-1 ring-slate-200"><kbd class="font-semibold text-slate-700">{{ $key }}</kbd> {{ $label }}</span>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <div class="card relative p-4">
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-slate-400"/>
                        <input x-ref="search" type="text" x-model="query" @input.debounce.200ms="search()" autocomplete="off" autofocus
                               class="form-input py-4 pl-14 text-lg" placeholder="Escanee el código de barras o escriba el nombre del producto (F2)">
                    </div>
                    <div x-show="results.length && query" x-cloak class="absolute left-4 right-4 z-30 mt-2 max-h-96 overflow-auto rounded-xl bg-white py-1 shadow-2xl ring-1 ring-black/5">
                        <template x-for="(r, i) in results" :key="r.id">
                            <button type="button" @click="add(r)" @mouseenter="highlight = i"
                                    class="flex w-full items-center justify-between gap-4 px-4 py-2.5 text-left" :class="i === highlight ? 'bg-indigo-50' : ''">
                                <span><span class="block font-medium text-slate-800" x-text="r.name"></span><span class="text-xs text-slate-400" x-text="r.sku + (r.barcode ? ' · ' + r.barcode : '')"></span></span>
                                <span class="text-right"><span class="block font-semibold" x-text="formatMoney(r.price)"></span>
                                    <span class="text-xs" :class="r.stock !== null && r.stock <= 0 ? 'text-rose-500' : 'text-slate-400'" x-show="r.stock !== null" x-text="'Stock ' + r.stock"></span></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="notFound" x-cloak class="mt-2 text-sm text-rose-600">No se encontró ningún producto con "<span x-text="notFound"></span>".</p>
                </div>

                <div class="card">
                    <table class="table">
                        <thead><tr><th>Producto</th><th class="w-36 text-center">Cantidad</th><th class="w-32 text-right">Precio</th><th class="w-24 text-right">Desc. %</th><th class="w-32 text-right">Total</th><th class="w-10"></th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(item, i) in items" :key="item.product_id">
                                <tr @click="selected = i" class="cursor-pointer" :class="i === selected ? '!bg-indigo-50/80' : ''">
                                    <td><span class="font-medium" x-text="item.name"></span><span class="block text-xs text-slate-400" x-text="item.sku + ' · IVA ' + item.tax_rate + '%'"></span></td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" class="btn-ghost btn-sm" @click.stop="changeQty(i, -1)">−</button>
                                            <input type="number" step="any" min="0" class="form-input w-16 py-1 text-center" x-model.number="item.quantity" @click.stop>
                                            <button type="button" class="btn-ghost btn-sm" @click.stop="changeQty(i, 1)">+</button>
                                        </div>
                                    </td>
                                    <td><input type="number" step="0.01" min="0" class="form-input py-1 text-right" x-model.number="item.unit_price" @click.stop></td>
                                    <td><input type="number" step="0.01" min="0" max="100" class="form-input py-1 text-right" x-model.number="item.discount_percent" @click.stop></td>
                                    <td class="text-right font-semibold" x-text="formatMoney(lineTotal(item))"></td>
                                    <td><button type="button" class="text-slate-400 hover:text-rose-600" @click.stop="remove(i)"><x-icon name="trash" class="h-4 w-4"/></button></td>
                                </tr>
                            </template>
                            <tr x-show="!items.length"><td colspan="6" class="py-16 text-center text-slate-400">
                                <x-icon name="barcode" class="mx-auto mb-2 h-10 w-10"/>Escanee un producto para comenzar la venta
                            </td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-5">
                <div class="card card-body space-y-4">
                    <div>
                        <label class="form-label">Cliente (F4)</label>
                        <select x-ref="customer" x-model="customerId" class="form-select">
                            @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}{{ $c->tax_id ? ' · '.$c->tax_id : '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="rounded-xl bg-slate-900 p-5 text-white">
                        <div class="flex justify-between text-sm text-slate-300"><span>Subtotal</span><span x-text="formatMoney(subtotal)"></span></div>
                        <div class="mt-1 flex justify-between text-sm text-slate-300"><span>Impuestos</span><span x-text="formatMoney(tax)"></span></div>
                        <div class="mt-3 flex items-end justify-between border-t border-white/10 pt-3"><span class="text-sm text-slate-300">TOTAL</span><span class="text-3xl font-bold" x-text="formatMoney(total)"></span></div>
                        <p class="mt-1 text-right text-xs text-slate-400" x-text="items.length + ' productos · ' + units + ' unidades'"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="form-label">Pago recibido (F8)</label>
                            <input x-ref="paid" type="number" step="0.01" min="0" x-model="paid" class="form-input py-3 text-xl font-semibold" placeholder="0,00">
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <button type="button" class="btn-secondary btn-sm" @click="paid = total.toFixed(2)">Exacto</button>
                                @foreach ([10, 20, 50, 100] as $bill)<button type="button" class="btn-secondary btn-sm" @click="paid = (Math.ceil(total / {{ $bill }}) * {{ $bill }}).toFixed(2)">{{ $bill }}</button>@endforeach
                                <button type="button" class="btn-ghost btn-sm" @click="paid = ''">A crédito</button>
                            </div>
                        </div>
                        <div><label class="form-label">Método</label>
                            <select x-model="method" class="form-select">@foreach (\App\Models\Payment::METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div><label class="form-label">Cuenta</label>
                            <select x-model="accountId" class="form-select">@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
                    </div>
                    <div class="flex justify-between rounded-lg bg-emerald-50 px-4 py-3 text-emerald-800" x-show="Number(paid) > 0">
                        <span class="text-sm font-medium" x-text="Number(paid) >= total ? 'Cambio' : 'Queda pendiente'"></span>
                        <span class="text-lg font-bold" x-text="formatMoney(Math.abs(Number(paid) - total))"></span>
                    </div>
                    <p x-show="error" x-cloak class="rounded-lg bg-rose-50 p-3 text-sm text-rose-700" x-text="error"></p>
                    <button type="button" @click="save()" :disabled="saving || !items.length" class="btn-primary w-full py-3 text-base">
                        <span x-show="!saving">Cobrar y facturar (F9)</span><span x-show="saving">Guardando…</span>
                    </button>
                    <button type="button" @click="reset()" class="btn-ghost w-full">Cancelar venta</button>
                </div>
            </div>
        </div>

        {{-- Venta completada --}}
        <div x-show="done" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-2xl" @click.outside="newSale()">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><x-icon name="check" class="h-8 w-8"/></div>
                <h2 class="text-xl font-bold">¡Venta registrada!</h2>
                <p class="mt-1 text-slate-500" x-text="done?.message"></p>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <a :href="done?.print_url" target="_blank" class="btn-secondary"><x-icon name="printer" class="h-4 w-4"/> Imprimir (P)</a>
                    <button type="button" @click="newSale()" class="btn-primary">Nueva venta (Enter)</button>
                </div>
                <a :href="done?.show_url" class="mt-4 inline-block text-sm link">Ver factura</a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('quickInvoice', (cfg) => ({
                query: '', results: [], highlight: 0, notFound: '', items: [], selected: -1,
                customerId: cfg.defaultCustomerId, paid: '', method: 'cash', accountId: cfg.defaultAccountId,
                saving: false, done: null, error: '', controller: null,

                get subtotal() { return this.items.reduce((s, i) => s + this.lineSubtotal(i), 0) },
                get tax() { return this.items.reduce((s, i) => s + this.lineSubtotal(i) * i.tax_rate / 100, 0) },
                get total() { return Math.round((this.subtotal + this.tax) * 100) / 100 },
                get units() { return this.items.reduce((s, i) => s + Number(i.quantity || 0), 0) },
                lineSubtotal(i) { const g = Number(i.quantity || 0) * Number(i.unit_price || 0); return Math.round((g - g * Number(i.discount_percent || 0) / 100) * 100) / 100 },
                lineTotal(i) { const s = this.lineSubtotal(i); return Math.round((s + s * i.tax_rate / 100) * 100) / 100 },

                async fetchResults(term) {
                    const url = cfg.lookupUrl + '?q=' + encodeURIComponent(term) + '&customer_id=' + encodeURIComponent(this.customerId || '');
                    const res = await fetch(url, { headers: { Accept: 'application/json' } });
                    return res.ok ? res.json() : { exact: false, results: [] };
                },
                async search() {
                    this.notFound = '';
                    const term = this.query.trim();
                    if (!term) { this.results = []; return; }
                    const data = await this.fetchResults(term);
                    if (term !== this.query.trim()) return;
                    this.results = data.results; this.highlight = 0;
                },
                async submitSearch() {
                    const term = this.query.trim();
                    if (!term) return;
                    if (this.results.length && this.results[this.highlight]) { this.add(this.results[this.highlight]); return; }
                    const data = await this.fetchResults(term);
                    if (data.results.length === 1 || data.exact) { this.add(data.results[0]); }
                    else if (data.results.length) { this.results = data.results; this.highlight = 0; }
                    else { this.notFound = term; this.query = ''; }
                },
                add(product) {
                    const index = this.items.findIndex(i => i.product_id === product.id);
                    if (index >= 0) { this.items[index].quantity = Number(this.items[index].quantity) + 1; this.selected = index; }
                    else {
                        this.items.push({ product_id: product.id, name: product.name, sku: product.sku, quantity: 1, unit_price: Number(product.price), discount_percent: 0, tax_rate_id: product.tax_rate_id, tax_rate: Number(product.tax_rate) });
                        this.selected = this.items.length - 1;
                    }
                    this.query = ''; this.results = []; this.notFound = '';
                    this.$nextTick(() => this.$refs.search.focus());
                },
                changeQty(i, delta) { const item = this.items[i]; if (!item) return; item.quantity = Math.max(0, Number(item.quantity) + delta); if (item.quantity === 0) this.remove(i); },
                remove(i) { this.items.splice(i, 1); this.selected = Math.min(this.selected, this.items.length - 1); },
                reset() { this.items = []; this.selected = -1; this.paid = ''; this.error = ''; this.query = ''; this.results = []; this.$nextTick(() => this.$refs.search.focus()); },
                newSale() { this.done = null; this.reset(); },

                async save() {
                    if (this.saving || !this.items.length) return;
                    this.saving = true; this.error = '';
                    try {
                        const res = await fetch(cfg.storeUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({
                                customer_id: this.customerId,
                                items: this.items.map(i => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price, discount_percent: i.discount_percent, tax_rate_id: i.tax_rate_id })),
                                paid_amount: this.paid === '' ? null : Number(this.paid),
                                account_id: this.paid === '' ? null : this.accountId,
                                method: this.method,
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok) { this.error = data.message || 'No se pudo guardar la venta.'; return; }
                        this.done = data;
                    } catch (e) { this.error = 'Error de conexión. Inténtelo de nuevo.'; }
                    finally { this.saving = false; }
                },

                onKey(e) {
                    if (this.done) {
                        if (e.key === 'Enter' || e.key === 'Escape') { e.preventDefault(); this.newSale(); }
                        if (e.key.toLowerCase() === 'p') { e.preventDefault(); window.open(this.done.print_url, '_blank'); }
                        return;
                    }
                    const inSearch = document.activeElement === this.$refs.search;
                    switch (e.key) {
                        case 'F2': e.preventDefault(); this.$refs.search.focus(); this.$refs.search.select(); return;
                        case 'F4': e.preventDefault(); this.$refs.customer.focus(); return;
                        case 'F8': e.preventDefault(); this.$refs.paid.focus(); this.$refs.paid.select(); return;
                        case 'F9': e.preventDefault(); this.save(); return;
                        case 'Escape': this.query = ''; this.results = []; this.notFound = ''; this.$refs.search.focus(); return;
                    }
                    if (e.key === 'Enter' && e.ctrlKey) { e.preventDefault(); this.save(); return; }
                    if (inSearch) {
                        if (e.key === 'Enter') { e.preventDefault(); this.submitSearch(); return; }
                        if (this.results.length && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                            e.preventDefault();
                            this.highlight = (this.highlight + (e.key === 'ArrowDown' ? 1 : -1) + this.results.length) % this.results.length;
                            return;
                        }
                        if (this.query === '' && this.items.length) {
                            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); this.selected = Math.max(0, Math.min(this.items.length - 1, this.selected + (e.key === 'ArrowDown' ? 1 : -1))); }
                            if (e.key === '+') { e.preventDefault(); this.changeQty(this.selected, 1); }
                            if (e.key === '-') { e.preventDefault(); this.changeQty(this.selected, -1); }
                            if (e.key === 'Delete') { e.preventDefault(); this.remove(this.selected); }
                        }
                    }
                },
            }));
        });
    </script>
    @endpush
</x-app-layout>
