@php extract(\App\Support\DocumentTemplate::vars($document, $company, $customFields, $partyFields)); $navy = '#0f2a4a'; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle }} {{ $document->number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif; font-size: 10.5px; color: #1f2937; }
        .page { padding: 0 0 36px; }
        .inner { padding: 0 40px; }
        .items th { padding: 8px 7px; font-size: 9.5px; text-transform: uppercase; }
        .items td { padding: 8px 7px; font-size: 10.5px; }
        .totals td { padding: 6px 10px; font-size: 10.5px; background: #f3f4f6; border-bottom: 1px solid #fff; }
        .t-label { color: #374151; }
        .t-value { text-align: right; }
        .n-title { margin: 0 0 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .n-text { margin: 0 0 12px; font-size: 10px; color: #4b5563; line-height: 1.5; }
        .label { font-size: 8.5px; text-transform: uppercase; letter-spacing: .6px; color: #6b7280; }
    </style>
</head>
<body>
@include('documents.templates.partials.toolbar')
<div class="sheet">
    <div class="page">
        <table width="100%" cellspacing="0" cellpadding="0" style="background: {{ $navy }}; color: #fff;">
            <tr>
                <td style="padding: 26px 40px; vertical-align: middle;">
                    <table cellspacing="0" cellpadding="0"><tr>
                        @if ($logo)<td style="padding-right: 14px;"><img src="{{ $logo }}" alt="" style="max-height: 54px; max-width: 150px; background: #fff; padding: 4px; border-radius: 4px;"></td>@endif
                        <td>
                            <div style="font-size: 17px; font-weight: bold;">{{ $company->name }}</div>
                            <div style="font-size: 9.5px; color: #cbd5e1; line-height: 1.5;">{{ $companyLines->implode(' | ') }}</div>
                        </td>
                    </tr></table>
                </td>
                <td style="padding: 26px 40px; text-align: right; vertical-align: middle; width: 36%;">
                    <div style="font-size: 20px; font-weight: bold; letter-spacing: 1px;">{{ $docTitle }}</div>
                    <div style="font-size: 12px; color: {{ $color }}; background: #fff; display: inline-block; padding: 3px 10px; margin-top: 6px; font-weight: bold;">{{ $document->number }}</div>
                </td>
            </tr>
        </table>
        <div style="height: 5px; background: {{ $color }};"></div>

        <div class="inner">
            <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 24px; border: 1px solid #e5e7eb;">
                <tr>
                    <td style="padding: 14px 16px; width: 50%; vertical-align: top; border-right: 1px solid #e5e7eb;">
                        <div class="label">{{ $partyTitle }}</div>
                        <div style="font-size: 13px; font-weight: bold; color: {{ $navy }}; margin-top: 3px;">{{ $party?->name ?? '—' }}</div>
                        @foreach ($partyLines as $line)<div style="font-size: 10px; color: #4b5563; line-height: 1.5;">{{ $line }}</div>@endforeach
                        @foreach ($partyExtra as $field)<div style="font-size: 10px; color: #4b5563;">{{ $field->label }}: {{ $field->displayValue($party->custom_fields[$field->name]) }}</div>@endforeach
                    </td>
                    <td style="padding: 0; vertical-align: top;">
                        <table width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;"><div class="label">Fecha de emisión</div><div style="font-weight: bold; margin-top: 2px;">{{ fdate($document->date) }}</div></td>
                                <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb;"><div class="label">{{ $dueLabel }}</div><div style="font-weight: bold; margin-top: 2px;">{{ fdate($document->due_date) }}</div></td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 16px;"><div class="label">Referencia</div><div style="font-weight: bold; margin-top: 2px;">{{ $document->reference ?: ($document->parent?->number ?? '—') }}</div></td>
                                <td style="padding: 12px 16px;"><div class="label">Sucursal</div><div style="font-weight: bold; margin-top: 2px;">{{ $document->branch?->name ?? '—' }}</div></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 22px;">
                @include('documents.templates.partials.items', ['thStyle' => 'background:'.$navy.'; color:#fff;', 'rowBorder' => 'border-bottom:1px solid #e5e7eb;', 'zebra' => '#f9fafb'])
            </div>

            <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 14px;">
                <tr>
                    <td style="vertical-align: top; padding-right: 20px; font-size: 9.5px; color: #6b7280;">
                        <div style="border-left: 3px solid {{ $color }}; padding-left: 10px;">
                            <strong style="color: {{ $navy }};">Estado: {{ $document->statusLabel() }}</strong><br>
                            Documento emitido por {{ $company->name }}{{ $company->tax_id ? ' · '.$company->tax_id : '' }}.
                        </div>
                    </td>
                    <td style="width: 44%;">
                        @include('documents.templates.partials.totals', ['totalStyle' => 'background:'.$color.'; color:#fff; font-size:14px; font-weight:bold; padding:10px;'])
                    </td>
                </tr>
            </table>

            @include('documents.templates.partials.notes', ['accent' => $navy])
        </div>
    </div>
</div>
</body>
</html>
