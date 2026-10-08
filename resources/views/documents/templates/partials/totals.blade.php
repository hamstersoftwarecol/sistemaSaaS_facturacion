{{-- Variables: $totalStyle (estilo de la fila total) --}}
<table class="totals" cellspacing="0" cellpadding="0" width="100%">
    <tr><td class="t-label">Subtotal</td><td class="t-value">{{ $m($document->subtotal) }}</td></tr>
    @if ((float) $document->discount_total > 0)
        <tr><td class="t-label">Descuentos incluidos</td><td class="t-value">-{{ $m($document->discount_total) }}</td></tr>
    @endif
    <tr><td class="t-label">Impuestos</td><td class="t-value">{{ $m($document->tax_total) }}</td></tr>
    @foreach ($document->adjustments ?? [] as $adj)
        <tr><td class="t-label">{{ $adj['name'] }}</td><td class="t-value">{{ $adj['action'] === 'subtract' ? '-' : '' }}{{ $m($adj['amount']) }}</td></tr>
    @endforeach
    <tr><td class="t-label" style="{{ $totalStyle }}">TOTAL</td><td class="t-value" style="{{ $totalStyle }}">{{ $m($document->total) }}</td></tr>
    @if ($showPayments)
        <tr><td class="t-label">Pagado</td><td class="t-value">{{ $m($document->amount_paid) }}</td></tr>
        <tr><td class="t-label" style="font-weight:bold;">Saldo pendiente</td><td class="t-value" style="font-weight:bold;">{{ $m($document->balance) }}</td></tr>
    @endif
</table>
