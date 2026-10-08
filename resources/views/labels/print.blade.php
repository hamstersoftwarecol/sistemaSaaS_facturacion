<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Etiquetas de código de barras</title>
    <style>
        @page { size: A4; margin: 8mm; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #e2e8f0; }
        .bar { position: sticky; top: 0; background: #0f172a; color: #fff; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; font-size: 14px; }
        .bar button { background: #4f46e5; color: #fff; border: 0; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 14px; }
        .sheet { width: 194mm; margin: 16px auto; background: #fff; padding: 4mm; display: grid; grid-template-columns: repeat({{ $columns }}, 1fr); gap: 3mm; }
        .label { border: 1px dashed #cbd5e1; border-radius: 4px; padding: 3mm 2mm; text-align: center; break-inside: avoid; overflow: hidden; }
        .label svg { max-width: 100%; height: 38px; }
        .name { font-size: 10px; font-weight: bold; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
        .code { font-family: 'Courier New', monospace; font-size: 10px; letter-spacing: 1px; margin-top: 1px; }
        .price { font-size: 13px; font-weight: bold; margin-top: 2px; }
        .company { font-size: 7px; color: #64748b; text-transform: uppercase; }
        @media print { body { background: #fff; } .bar { display: none; } .sheet { margin: 0; width: auto; padding: 0; } .label { border-color: #e2e8f0; } }
    </style>
</head>
<body>
    <div class="bar"><span>{{ count($labels) }} etiquetas · {{ $columns }} columnas</span><button onclick="window.print()">Imprimir</button></div>
    <div class="sheet">
        @foreach ($labels as $label)
            <div class="label">
                <div class="company">{{ $company->name }}</div>
                @if ($showName)<div class="name">{{ $label['product']->name }}</div>@endif
                {!! $label['svg'] !!}
                <div class="code">{{ $label['product']->barcodeValue() }}</div>
                @if ($showPrice)<div class="price">{{ money($label['product']->sale_price, $company->currency_symbol) }}</div>@endif
            </div>
        @endforeach
    </div>
</body>
</html>
