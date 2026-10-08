{{-- Notas, términos, campos personalizados y bloque de verificación QR. Variables: $accent --}}
<table width="100%" cellspacing="0" cellpadding="0" style="margin-top:26px;">
    <tr>
        <td style="vertical-align:top; padding-right:20px;">
            @if ($document->notes)
                <p class="n-title" style="color:{{ $accent }};">Notas</p>
                <p class="n-text">{!! nl2br(e($document->notes)) !!}</p>
            @endif
            @if ($document->terms)
                <p class="n-title" style="color:{{ $accent }};">Términos y condiciones</p>
                <p class="n-text">{!! nl2br(e($document->terms)) !!}</p>
            @endif
            @foreach ($docExtra as $field)
                <p class="n-text"><strong>{{ $field->label }}:</strong> {{ $field->displayValue($document->custom_fields[$field->name]) }}</p>
            @endforeach
        </td>
        <td style="vertical-align:top; width:200px;">
            <table cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0; border-radius:8px;" width="100%">
                <tr>
                    <td style="padding:8px; width:92px;"><img src="{{ $qr }}" alt="QR" style="width:88px; height:88px;"></td>
                    <td style="padding:8px 8px 8px 0; font-size:9px; color:#64748b; vertical-align:middle;">
                        <strong style="color:#0f172a; font-size:10px;">Verificación</strong><br>
                        Escanee el código para comprobar la autenticidad de este documento.<br>
                        <span style="font-family:'DejaVu Sans Mono', monospace; font-size:8px;">{{ strtoupper(substr($document->verification_code ?? '', 0, 12)) }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@if ($company->invoice_footer)
    <p style="margin-top:22px; text-align:center; font-size:9px; color:#94a3b8;">{{ $company->invoice_footer }}</p>
@endif
