@php extract(\App\Support\DocumentTemplate::vars($document, $company, $customFields, $partyFields)); $gold = '#a47e3b'; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle }} {{ $document->number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif; font-size: 10.5px; color: #3f3f46; }
        .page { padding: 54px 56px; }
        .serif { font-family: 'DejaVu Serif', Georgia, serif; }
        .items th { padding: 10px 6px; font-size: 8.5px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: normal; color: #71717a; }
        .items td { padding: 11px 6px; font-size: 10.5px; }
        .totals td { padding: 6px 0; font-size: 10.5px; }
        .t-label { color: #71717a; letter-spacing: .5px; }
        .t-value { text-align: right; }
        .n-title { margin: 0 0 4px; font-size: 8.5px; letter-spacing: 1.8px; text-transform: uppercase; }
        .n-text { margin: 0 0 14px; font-size: 10px; color: #52525b; line-height: 1.6; }
        .eyebrow { font-size: 8.5px; letter-spacing: 2px; text-transform: uppercase; color: {{ $gold }}; }
    </style>
</head>
<body>
@include('documents.templates.partials.toolbar')
<div class="sheet">
    <div class="page">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td style="vertical-align: top;">
                    @if ($logo)<img src="{{ $logo }}" alt="" style="max-height: 52px; max-width: 170px; margin-bottom: 12px;"><br>@endif
                    <div class="serif" style="font-size: 20px; color: #18181b; letter-spacing: .5px;">{{ $company->name }}</div>
                    @foreach ($companyLines as $line)<div style="font-size: 9.5px; color: #71717a; line-height: 1.6;">{{ $line }}</div>@endforeach
                </td>
                <td style="vertical-align: top; text-align: right;">
                    <div class="serif" style="font-size: 30px; color: #18181b; font-style: italic;">{{ mb_convert_case(mb_strtolower($docTitle), MB_CASE_TITLE) }}</div>
                    <div class="eyebrow" style="margin-top: 4px;">N.º {{ $document->number }}</div>
                </td>
            </tr>
        </table>

        <div style="border-top: 1px solid {{ $gold }}; margin: 26px 0 0; height: 1px;"></div>
        <div style="border-top: 1px solid #e4e4e7; margin-top: 3px;"></div>

        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 22px;">
            <tr>
                <td style="vertical-align: top; width: 50%;">
                    <div class="eyebrow">{{ $partyTitle }}</div>
                    <div class="serif" style="font-size: 15px; color: #18181b; margin-top: 6px;">{{ $party?->name ?? '—' }}</div>
                    @foreach ($partyLines as $line)<div style="font-size: 10px; color: #71717a; line-height: 1.6;">{{ $line }}</div>@endforeach
                    @foreach ($partyExtra as $field)<div style="font-size: 10px; color: #71717a;">{{ $field->label }}: {{ $field->displayValue($party->custom_fields[$field->name]) }}</div>@endforeach
                </td>
                <td style="vertical-align: top;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="text-align: right; padding-bottom: 10px;"><div class="eyebrow">Fecha</div><div style="margin-top: 3px; color: #18181b;">{{ fdate($document->date, 'd M Y') }}</div></td>
                            @if ($document->due_date)<td style="text-align: right; padding-bottom: 10px;"><div class="eyebrow">{{ $dueLabel }}</div><div style="margin-top: 3px; color: #18181b;">{{ fdate($document->due_date, 'd M Y') }}</div></td>@endif
                        </tr>
                        @if ($document->reference || $document->parent)
                            <tr><td colspan="2" style="text-align: right;"><div class="eyebrow">Referencia</div><div style="margin-top: 3px; color: #18181b;">{{ $document->reference ?: $document->parent?->number }}</div></td></tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <div style="margin-top: 30px;">
            @include('documents.templates.partials.items', ['thStyle' => 'border-bottom:1px solid '.$gold.';', 'rowBorder' => 'border-bottom:1px solid #f4f4f5;', 'zebra' => null])
        </div>

        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 18px;">
            <tr>
                <td></td>
                <td style="width: 40%;">
                    @include('documents.templates.partials.totals', ['totalStyle' => 'border-top:1px solid '.$gold.'; padding-top:12px; font-family:\'DejaVu Serif\', Georgia, serif; font-size:16px; color:#18181b;'])
                </td>
            </tr>
        </table>

        @include('documents.templates.partials.notes', ['accent' => $gold])

        <p class="serif" style="margin-top: 30px; text-align: center; font-style: italic; color: {{ $gold }}; font-size: 12px;">Gracias por su confianza</p>
    </div>
</div>
</body>
</html>
