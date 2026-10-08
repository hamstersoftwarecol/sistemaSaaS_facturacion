@php extract(\App\Support\DocumentTemplate::vars($document, $company, $customFields, $partyFields)); $dark = '#111827'; @endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle }} {{ $document->number }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10.5px; color: {{ $dark }}; }
        .page { padding: 0 0 36px; }
        .inner { padding: 0 42px; }
        .items th { padding: 10px 8px; font-size: 9.5px; text-transform: uppercase; letter-spacing: .6px; }
        .items td { padding: 10px 8px; font-size: 10.5px; }
        .totals td { padding: 6px 12px; font-size: 11px; }
        .t-label { color: #4b5563; }
        .t-value { text-align: right; font-weight: bold; }
        .n-title { margin: 0 0 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .n-text { margin: 0 0 12px; font-size: 10px; color: #4b5563; line-height: 1.5; }
    </style>
</head>
<body>
@include('documents.templates.partials.toolbar')
<div class="sheet">
    <div class="page">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td style="background: {{ $color }}; color: #fff; padding: 34px 42px 30px; vertical-align: bottom;">
                    <div style="font-size: 44px; font-weight: bold; line-height: 1; letter-spacing: -1px;">{{ $docTitle }}</div>
                    <div style="font-size: 13px; margin-top: 8px; opacity: .9;"># {{ $document->number }} &nbsp;·&nbsp; {{ fdate($document->date) }}</div>
                </td>
                <td style="background: {{ $dark }}; color: #fff; padding: 30px 32px; width: 36%; vertical-align: top;">
                    @if ($logo)<img src="{{ $logo }}" alt="" style="max-height: 44px; max-width: 140px; background: #fff; padding: 4px; border-radius: 6px; margin-bottom: 8px;"><br>@endif
                    <div style="font-size: 14px; font-weight: bold;">{{ $company->name }}</div>
                    @foreach ($companyLines as $line)<div style="font-size: 9px; color: #d1d5db; line-height: 1.5;">{{ $line }}</div>@endforeach
                </td>
            </tr>
        </table>

        <div class="inner">
            <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 28px;">
                <tr>
                    <td style="vertical-align: top; width: 52%;">
                        <div style="display: inline-block; background: {{ $color }}; color: #fff; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; padding: 3px 8px;">{{ $partyTitle }}</div>
                        <div style="font-size: 17px; font-weight: bold; margin-top: 8px;">{{ $party?->name ?? '—' }}</div>
                        @foreach ($partyLines as $line)<div style="font-size: 10px; color: #4b5563; line-height: 1.5;">{{ $line }}</div>@endforeach
                        @foreach ($partyExtra as $field)<div style="font-size: 10px; color: #4b5563;">{{ $field->label }}: {{ $field->displayValue($party->custom_fields[$field->name]) }}</div>@endforeach
                    </td>
                    <td style="vertical-align: top; text-align: right;">
                        <div style="font-size: 9px; font-weight: bold; text-transform: uppercase; color: #6b7280; letter-spacing: 1px;">Total a pagar</div>
                        <div style="font-size: 30px; font-weight: bold; color: {{ $color }}; line-height: 1.2;">{{ $m(in_array($document->type, ['invoice', 'purchase_order']) ? $document->balance : $document->total) }}</div>
                        @if ($document->due_date)<div style="font-size: 10px; margin-top: 4px;"><strong>{{ $dueLabel }}:</strong> {{ fdate($document->due_date) }}</div>@endif
                        @if ($document->reference)<div style="font-size: 10px;"><strong>Ref.:</strong> {{ $document->reference }}</div>@endif
                    </td>
                </tr>
            </table>

            <div style="margin-top: 26px;">
                @include('documents.templates.partials.items', ['thStyle' => 'background:'.$dark.'; color:#fff;', 'rowBorder' => 'border-bottom:2px solid #f3f4f6;', 'zebra' => null])
            </div>

            <table width="100%" cellspacing="0" cellpadding="0" style="margin-top: 16px;">
                <tr>
                    <td></td>
                    <td style="width: 44%; background: #f9fafb;">
                        @include('documents.templates.partials.totals', ['totalStyle' => 'background:'.$color.'; color:#fff; font-size:16px; font-weight:bold; padding:12px;'])
                    </td>
                </tr>
            </table>

            @include('documents.templates.partials.notes', ['accent' => $color])
        </div>
    </div>
</div>
</body>
</html>
