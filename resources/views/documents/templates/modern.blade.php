@php extract(\App\Support\DocumentTemplate::vars($document, $company, $customFields, $partyFields)); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle }} {{ $document->number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', 'Inter', Arial, sans-serif; font-size: 11px; color: #1e293b; }
        .page { padding: 40px 44px; }
        .muted { color: #64748b; }
        .items th { padding: 9px 8px; font-size: 9.5px; text-transform: uppercase; letter-spacing: .5px; }
        .items td { padding: 9px 8px; font-size: 10.5px; }
        .totals td { padding: 5px 0; font-size: 11px; }
        .t-label { color: #64748b; }
        .t-value { text-align: right; }
        .n-title { margin: 0 0 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .6px; }
        .n-text { margin: 0 0 12px; font-size: 10px; color: #475569; line-height: 1.5; }
    </style>
</head>
<body>
@include('documents.templates.partials.toolbar')
<div class="sheet">
    <div style="height: 10px; background: {{ $color }};"></div>
    <div class="page">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td style="vertical-align: top;">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" style="max-height: 60px; max-width: 200px; margin-bottom: 10px;">
                    @endif
                    <div style="font-size: 18px; font-weight: bold; color: #0f172a;">{{ $company->name }}</div>
                    @foreach ($companyLines as $line)<div class="muted" style="font-size: 10px; line-height: 1.5;">{{ $line }}</div>@endforeach
                </td>
                <td style="vertical-align: top; text-align: right; width: 45%;">
                    <div style="font-size: 26px; font-weight: bold; letter-spacing: 1px; color: {{ $color }};">{{ $docTitle }}</div>
                    <div style="font-size: 13px; font-weight: bold; margin-top: 2px;">N.º {{ $document->number }}</div>
                    <table cellspacing="0" cellpadding="0" style="margin-left: auto; margin-top: 10px; font-size: 10.5px;">
                        <tr><td class="muted" style="padding: 2px 10px 2px 0;">Fecha de emisión</td><td style="text-align: right; font-weight: bold;">{{ fdate($document->date) }}</td></tr>
                        @if ($document->due_date)<tr><td class="muted" style="padding: 2px 10px 2px 0;">{{ $dueLabel }}</td><td style="text-align: right; font-weight: bold;">{{ fdate($document->due_date) }}</td></tr>@endif
                        @if ($document->reference)<tr><td class="muted" style="padding: 2px 10px 2px 0;">Referencia</td><td style="text-align: right; font-weight: bold;">{{ $document->reference }}</td></tr>@endif
                        @if ($document->parent)<tr><td class="muted" style="padding: 2px 10px 2px 0;">Documento origen</td><td style="text-align: right; font-weight: bold;">{{ $document->parent->number }}</td></tr>@endif
                    </table>
                </td>
            </tr>
        </table>

        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 28px; background: #f8fafc; border-radius: 10px;">
            <tr>
                <td style="padding: 16px 18px; vertical-align: top;">
                    <div style="font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .8px; color: {{ $color }};">{{ $partyTitle }}</div>
                    <div style="font-size: 14px; font-weight: bold; margin-top: 4px;">{{ $party?->name ?? '—' }}</div>
                    @foreach ($partyLines as $line)<div class="muted" style="font-size: 10px; line-height: 1.5;">{{ $line }}</div>@endforeach
                    @foreach ($partyExtra as $field)<div class="muted" style="font-size: 10px;">{{ $field->label }}: {{ $field->displayValue($party->custom_fields[$field->name]) }}</div>@endforeach
                </td>
                <td style="padding: 16px 18px; vertical-align: top; text-align: right; width: 40%;">
                    <div style="font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .8px; color: #64748b;">{{ in_array($document->type, ['invoice', 'purchase_order']) ? 'Importe total' : 'Total' }}</div>
                    <div style="font-size: 22px; font-weight: bold; color: #0f172a; margin-top: 4px;">{{ $m($document->total) }}</div>
                    <div class="muted" style="font-size: 10px;">{{ $document->statusLabel() }}</div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 24px;">
            @include('documents.templates.partials.items', ['thStyle' => 'background:'.$color.'; color:#fff;', 'rowBorder' => 'border-bottom:1px solid #e2e8f0;', 'zebra' => null])
        </div>

        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 16px;">
            <tr>
                <td></td>
                <td style="width: 42%;">
                    @include('documents.templates.partials.totals', ['totalStyle' => 'padding-top:10px; border-top:2px solid '.$color.'; font-size:15px; font-weight:bold; color:'.$color.';'])
                </td>
            </tr>
        </table>

        @include('documents.templates.partials.notes', ['accent' => $color])
    </div>
</div>
</body>
</html>
