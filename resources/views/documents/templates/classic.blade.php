@php extract(\App\Support\DocumentTemplate::vars($document, $company, $customFields, $partyFields)); @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle }} {{ $document->number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Serif', Georgia, 'Times New Roman', serif; font-size: 11px; color: #111; }
        .page { padding: 44px 48px; }
        .items th { padding: 7px 6px; font-size: 10px; }
        .items td { padding: 7px 6px; font-size: 10.5px; }
        .totals td { padding: 4px 6px; font-size: 11px; border-bottom: 1px solid #ddd; }
        .t-label { }
        .t-value { text-align: right; }
        .n-title { margin: 0 0 4px; font-size: 11px; font-weight: bold; text-decoration: underline; }
        .n-text { margin: 0 0 12px; font-size: 10.5px; line-height: 1.5; }
        .box { border: 1px solid #111; padding: 10px 12px; }
    </style>
</head>
<body>
@include('documents.templates.partials.toolbar')
<div class="sheet">
    <div class="page">
        <div style="text-align: center; border-bottom: 3px double #111; padding-bottom: 14px;">
            @if ($logo)<img src="{{ $logo }}" alt="" style="max-height: 56px; max-width: 180px; margin-bottom: 6px;"><br>@endif
            <div style="font-size: 20px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;">{{ $company->name }}</div>
            <div style="font-size: 10px; margin-top: 4px;">{{ $companyLines->implode('  ·  ') }}</div>
        </div>

        <div style="text-align: center; margin: 20px 0 18px;">
            <span style="font-size: 18px; font-weight: bold; letter-spacing: 3px;">{{ $docTitle }}</span><br>
            <span style="font-size: 12px;">N.º {{ $document->number }}</span>
        </div>

        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td style="width: 58%; vertical-align: top; padding-right: 12px;">
                    <div class="box" style="min-height: 92px;">
                        <div style="font-weight: bold; font-size: 10px; text-transform: uppercase; margin-bottom: 4px;">{{ $partyTitle }}:</div>
                        <div style="font-size: 13px; font-weight: bold;">{{ $party?->name ?? '—' }}</div>
                        @foreach ($partyLines as $line)<div style="font-size: 10px; line-height: 1.5;">{{ $line }}</div>@endforeach
                        @foreach ($partyExtra as $field)<div style="font-size: 10px;">{{ $field->label }}: {{ $field->displayValue($party->custom_fields[$field->name]) }}</div>@endforeach
                    </div>
                </td>
                <td style="vertical-align: top;">
                    <table class="box" width="100%" cellspacing="0" cellpadding="0" style="font-size: 10.5px; min-height: 92px;">
                        <tr><td style="padding: 3px 0;">Fecha:</td><td style="text-align: right; font-weight: bold;">{{ fdate($document->date) }}</td></tr>
                        @if ($document->due_date)<tr><td style="padding: 3px 0;">{{ $dueLabel }}:</td><td style="text-align: right; font-weight: bold;">{{ fdate($document->due_date) }}</td></tr>@endif
                        @if ($document->reference)<tr><td style="padding: 3px 0;">Referencia:</td><td style="text-align: right; font-weight: bold;">{{ $document->reference }}</td></tr>@endif
                        @if ($document->parent)<tr><td style="padding: 3px 0;">Doc. origen:</td><td style="text-align: right; font-weight: bold;">{{ $document->parent->number }}</td></tr>@endif
                        <tr><td style="padding: 3px 0;">Estado:</td><td style="text-align: right; font-weight: bold;">{{ $document->statusLabel() }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div style="margin-top: 20px;">
            @include('documents.templates.partials.items', ['thStyle' => 'border-top:2px solid #111; border-bottom:2px solid #111; text-transform:uppercase;', 'rowBorder' => 'border-bottom:1px solid #bbb;', 'zebra' => null])
        </div>

        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 14px;">
            <tr>
                <td></td>
                <td style="width: 42%;">
                    @include('documents.templates.partials.totals', ['totalStyle' => 'border-top:2px solid #111; border-bottom:3px double #111; font-size:14px; font-weight:bold;'])
                </td>
            </tr>
        </table>

        @include('documents.templates.partials.notes', ['accent' => '#111'])

        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 40px;">
            <tr>
                <td style="width: 45%; border-top: 1px solid #111; text-align: center; padding-top: 6px; font-size: 10px;">Firma autorizada</td>
                <td></td>
                <td style="width: 45%; border-top: 1px solid #111; text-align: center; padding-top: 6px; font-size: 10px;">Recibí conforme</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
