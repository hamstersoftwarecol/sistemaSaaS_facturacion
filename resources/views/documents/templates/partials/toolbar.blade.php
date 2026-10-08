@if (empty($pdf))
    <div class="toolbar no-print">
        <div class="toolbar-inner">
            <div>
                @if (! empty($preview))
                    <a href="{{ route('settings.invoice') }}" class="tb-btn">&larr; Volver a configuración</a>
                @else
                    <a href="{{ route($document->routeName('show'), $document) }}" class="tb-btn">&larr; Volver</a>
                @endif
            </div>
            <div class="tb-styles">
                @foreach (\App\Models\Company::INVOICE_STYLES as $key => $label)
                    <a href="{{ ! empty($preview) ? route('settings.invoice.preview', $key) : route($document->routeName('print'), [$document, 'style' => $key]) }}"
                       class="tb-chip {{ $style === $key ? 'tb-chip-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div>
                @if (empty($preview))
                    <a href="{{ route($document->routeName('pdf'), [$document, 'style' => $style, 'download' => 1]) }}" class="tb-btn">Descargar PDF</a>
                @endif
                <button onclick="window.print()" class="tb-btn tb-primary">Imprimir</button>
            </div>
        </div>
    </div>
    <style>
        body { background: #e2e8f0; }
        .toolbar { position: sticky; top: 0; z-index: 10; background: #0f172a; padding: 10px 16px; font-family: 'Inter', 'DejaVu Sans', Arial, sans-serif; }
        .toolbar-inner { max-width: 1100px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; }
        .tb-btn { display: inline-block; padding: 7px 14px; border-radius: 8px; background: #1e293b; color: #e2e8f0 !important; font-size: 13px; text-decoration: none; border: 0; cursor: pointer; }
        .tb-btn:hover { background: #334155; }
        .tb-primary { background: #4f46e5; color: #fff !important; }
        .tb-primary:hover { background: #6366f1; }
        .tb-styles { display: flex; gap: 6px; flex-wrap: wrap; }
        .tb-chip { padding: 5px 12px; border-radius: 999px; font-size: 12px; color: #cbd5e1 !important; text-decoration: none; border: 1px solid #334155; }
        .tb-chip-active { background: #fff; color: #0f172a !important; border-color: #fff; }
        .sheet { width: 794px; min-height: 1123px; margin: 24px auto; background: #fff; box-shadow: 0 10px 40px rgba(15, 23, 42, .18); }
        @media print {
            body { background: #fff; }
            .sheet { margin: 0; box-shadow: none; width: auto; min-height: 0; }
            .no-print { display: none !important; }
        }
    </style>
@endif
