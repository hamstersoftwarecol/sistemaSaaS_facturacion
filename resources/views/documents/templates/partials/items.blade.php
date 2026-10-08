{{-- Variables: $thStyle, $rowBorder, $zebra (opcional) --}}
<table class="items" width="100%" cellspacing="0" cellpadding="0">
    <thead>
        <tr>
            <th style="{{ $thStyle }} width:4%; text-align:center;">#</th>
            <th style="{{ $thStyle }} text-align:left;">Descripción</th>
            <th style="{{ $thStyle }} width:10%; text-align:right;">Cant.</th>
            <th style="{{ $thStyle }} width:14%; text-align:right;">Precio</th>
            <th style="{{ $thStyle }} width:8%; text-align:right;">Desc.</th>
            <th style="{{ $thStyle }} width:8%; text-align:right;">Imp.</th>
            <th style="{{ $thStyle }} width:15%; text-align:right;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($document->items as $i => $item)
            <tr style="{{ ! empty($zebra) && $i % 2 ? 'background:'.$zebra.';' : '' }}">
                <td style="{{ $rowBorder }} text-align:center; color:#94a3b8;">{{ $i + 1 }}</td>
                <td style="{{ $rowBorder }}">{{ $item->description }}</td>
                <td style="{{ $rowBorder }} text-align:right;">{{ qty($item->quantity) }} {{ $item->unit }}</td>
                <td style="{{ $rowBorder }} text-align:right;">{{ $m($item->unit_price) }}</td>
                <td style="{{ $rowBorder }} text-align:right;">{{ (float) $item->discount_percent > 0 ? pct($item->discount_percent) : '—' }}</td>
                <td style="{{ $rowBorder }} text-align:right;">{{ pct($item->tax_rate) }}</td>
                <td style="{{ $rowBorder }} text-align:right; font-weight:bold;">{{ $m($item->total) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
