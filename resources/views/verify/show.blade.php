<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verificación de documento · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-100 to-indigo-100 p-4 font-sans">
    <div class="w-full max-w-lg">
        <a href="{{ route('home') }}" class="mb-6 flex items-center justify-center gap-2"><x-application-logo class="h-9 w-9"/><span class="font-semibold">{{ config('app.name') }}</span></a>
        <div class="card overflow-hidden">
            @if ($document)
                @php $valid = $document->status !== 'cancelled'; @endphp
                <div class="{{ $valid ? 'bg-emerald-500' : 'bg-rose-500' }} p-6 text-center text-white">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/20"><x-icon :name="$valid ? 'badge-check' : 'warning'" class="h-8 w-8"/></div>
                    <h1 class="text-xl font-bold">{{ $valid ? 'Documento auténtico' : 'Documento anulado' }}</h1>
                    <p class="text-sm opacity-90">{{ $valid ? 'Este documento fue emitido y registrado en nuestro sistema.' : 'Este documento existe pero fue anulado por el emisor.' }}</p>
                </div>
                <dl class="card-body space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Emisor</dt><dd class="text-right font-semibold">{{ $document->company->name }}{{ $document->company->tax_id ? ' · '.$document->company->tax_id : '' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Tipo</dt><dd class="font-semibold">{{ $document->cfg('label') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Número</dt><dd class="font-semibold">{{ $document->number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Fecha de emisión</dt><dd class="font-semibold">{{ fdate($document->date) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ $document->isSales() ? 'Cliente' : 'Proveedor' }}</dt><dd class="text-right font-semibold">{{ $document->party()?->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Importe total</dt><dd class="text-lg font-bold">{{ money($document->total, $document->company->currency_symbol) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Estado</dt><dd><x-badge :color="$document->statusColor()">{{ $document->statusLabel() }}</x-badge></dd></div>
                    <p class="pt-2 text-center font-mono text-xs text-slate-400">Código: {{ strtoupper($code) }}</p>
                </dl>
            @else
                <div class="bg-rose-500 p-6 text-center text-white">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/20"><x-icon name="x" class="h-8 w-8"/></div>
                    <h1 class="text-xl font-bold">Documento no encontrado</h1>
                    <p class="text-sm opacity-90">El código no corresponde a ningún documento emitido. Podría tratarse de un documento falsificado.</p>
                </div>
                <p class="card-body text-center font-mono text-xs text-slate-400">Código consultado: {{ $code }}</p>
            @endif
        </div>
        <p class="mt-6 text-center text-xs text-slate-500">Verificación segura de documentos electrónicos mediante código QR.</p>
    </div>
</body>
</html>
