<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo {{ $payment->number }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-200 py-8 print:bg-white print:py-0">
    <div class="no-print mx-auto mb-4 flex max-w-2xl justify-end gap-2">
        <a href="{{ route($meta['route'].'.show', $payment) }}" class="btn-secondary">Volver</a>
        <button onclick="window.print()" class="btn-primary">Imprimir</button>
    </div>
    <div class="mx-auto max-w-2xl bg-white p-10 shadow-lg print:shadow-none">
        <div class="flex items-start justify-between border-b-4 pb-5" style="border-color: {{ $company->primary_color }}">
            <div>
                @if ($company->logoDataUri())<img src="{{ $company->logoDataUri() }}" class="mb-2 h-12">@endif
                <p class="text-lg font-bold">{{ $company->name }}</p>
                <p class="text-xs text-slate-500">{{ $company->tax_id }} · {{ $company->fullAddress() }}</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold" style="color: {{ $company->primary_color }}">{{ $payment->direction === 'in' ? 'RECIBO DE CAJA' : 'COMPROBANTE DE EGRESO' }}</p>
                <p class="font-semibold">{{ $payment->number }}</p>
                <p class="text-sm text-slate-500">{{ fdate($payment->date) }}</p>
            </div>
        </div>
        <div class="mt-6 space-y-3 text-sm">
            <p><span class="text-slate-500">{{ $payment->direction === 'in' ? 'Recibimos de:' : 'Pagado a:' }}</span> <strong>{{ $payment->party()?->name }}</strong> {{ $payment->party()?->tax_id ? '('.$payment->party()->tax_id.')' : '' }}</p>
            <p><span class="text-slate-500">La suma de:</span> <strong class="text-lg">{{ money($payment->amount, $company->currency_symbol) }}</strong></p>
            <p><span class="text-slate-500">Concepto:</span> {{ $payment->document ? 'Pago de '.mb_strtolower($payment->document->cfg('label')).' '.$payment->document->number : 'Pago a cuenta / anticipo' }}</p>
            <p><span class="text-slate-500">Forma de pago:</span> {{ $payment->methodLabel() }} {{ $payment->reference ? '· Ref. '.$payment->reference : '' }}</p>
            @if ($payment->document)<p><span class="text-slate-500">Saldo pendiente del documento:</span> {{ money($payment->document->balance, $company->currency_symbol) }}</p>@endif
            @if ($payment->notes)<p><span class="text-slate-500">Observaciones:</span> {{ $payment->notes }}</p>@endif
        </div>
        <div class="mt-16 grid grid-cols-2 gap-10 text-center text-xs text-slate-500">
            <div class="border-t border-slate-400 pt-2">Elaborado por: {{ $payment->user?->name }}</div>
            <div class="border-t border-slate-400 pt-2">Firma y sello</div>
        </div>
    </div>
</body>
</html>
