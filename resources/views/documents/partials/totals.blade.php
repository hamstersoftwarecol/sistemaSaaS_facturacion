<div class="card">
    <div class="card-header">
        <h2 class="card-title">Resumen</h2>
        <span x-show="loading" class="text-xs text-slate-400">Calculando…</span>
    </div>
    <dl class="card-body space-y-2.5 text-sm">
        <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-medium" x-text="formatMoney(totals.subtotal)"></dd></div>
        <div class="flex justify-between" x-show="Number(totals.discount_total) > 0"><dt class="text-slate-500">Descuentos incluidos</dt><dd class="text-slate-500" x-text="'-' + formatMoney(totals.discount_total)"></dd></div>
        <div class="flex justify-between"><dt class="text-slate-500">Impuestos</dt><dd class="font-medium" x-text="formatMoney(totals.tax_total)"></dd></div>
        <template x-for="adj in (totals.adjustments || [])" :key="adj.code">
            <div class="flex justify-between">
                <dt class="text-slate-500"><span x-text="adj.name"></span> <span class="text-xs text-indigo-500">(fórmula)</span></dt>
                <dd class="font-medium" :class="adj.action === 'subtract' ? 'text-rose-600' : (adj.action === 'info' ? 'text-slate-400' : '')"
                    x-text="(adj.action === 'subtract' ? '-' : '') + formatMoney(adj.amount)"></dd>
            </div>
        </template>
        <div class="flex justify-between border-t border-slate-200 pt-3 text-base"><dt class="font-semibold text-slate-900">Total</dt><dd class="font-bold text-slate-900" x-text="formatMoney(totals.total)"></dd></div>
    </dl>
</div>
