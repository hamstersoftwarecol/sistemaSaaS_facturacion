@php
    $styleInfo = [
        'modern' => ['Moderno', 'Limpio, con franja de color y tarjeta de totales.', 'from-indigo-500 to-violet-500'],
        'classic' => ['Clásico', 'Tipografía serif, bordes dobles y espacio para firmas.', 'from-slate-700 to-slate-900'],
        'corporate' => ['Corporativo', 'Cabecera azul marino, tablas a rayas y datos en rejilla.', 'from-blue-900 to-sky-700'],
        'elegant' => ['Elegante', 'Detalles dorados, mucho aire y títulos en cursiva.', 'from-amber-600 to-yellow-500'],
        'bold' => ['Llamativo', 'Bloques de color, título gigante y total destacado.', 'from-rose-500 to-orange-500'],
    ];
@endphp
<x-app-layout title="Diseño de facturas">
    <x-page-header title="Diseño de documentos" subtitle="Estilo de factura con verificación QR, numeración y textos predeterminados"/>
    <form method="POST" action="{{ route('settings.invoice.update') }}" class="space-y-6" x-data="{ style: @js(old('invoice_style', $company->invoice_style)), color: @js(old('primary_color', $company->primary_color)) }">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-header"><h2 class="card-title">Estilo de factura (5 diseños con código QR)</h2></div>
            <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($styleInfo as $key => [$label, $desc, $gradient])
                    <label class="group cursor-pointer rounded-xl border-2 p-3 transition" :class="style === '{{ $key }}' ? 'border-indigo-500 ring-4 ring-indigo-100' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="invoice_style" value="{{ $key }}" x-model="style" class="sr-only">
                        <div class="relative h-36 overflow-hidden rounded-lg bg-white ring-1 ring-slate-200">
                            <div class="h-8 bg-gradient-to-r {{ $gradient }}"></div>
                            <div class="space-y-1.5 p-2.5">
                                <div class="h-1.5 w-2/3 rounded bg-slate-200"></div>
                                <div class="h-1.5 w-1/2 rounded bg-slate-200"></div>
                                <div class="mt-3 h-1.5 rounded bg-slate-100"></div>
                                <div class="h-1.5 rounded bg-slate-100"></div>
                                <div class="h-1.5 rounded bg-slate-100"></div>
                                <div class="ml-auto mt-2 h-2.5 w-1/3 rounded bg-gradient-to-r {{ $gradient }}"></div>
                            </div>
                            <div class="absolute bottom-2 left-2.5 grid h-6 w-6 grid-cols-3 gap-px"><span class="bg-slate-800"></span><span></span><span class="bg-slate-800"></span><span></span><span class="bg-slate-800"></span><span></span><span class="bg-slate-800"></span><span class="bg-slate-800"></span><span class="bg-slate-800"></span></div>
                        </div>
                        <p class="mt-2 text-sm font-semibold">{{ $label }}</p>
                        <p class="text-xs text-slate-500">{{ $desc }}</p>
                        <a href="{{ route('settings.invoice.preview', $key) }}" target="_blank" class="mt-1 inline-block text-xs link" @click.stop>Vista previa →</a>
                    </label>
                @endforeach
            </div>
            <div class="card-body flex flex-wrap items-end gap-5 border-t border-slate-100">
                <div>
                    <label class="form-label">Color principal</label>
                    <div class="flex items-center gap-2">
                        <input type="color" x-model="color" class="h-10 w-14 cursor-pointer rounded border border-slate-300">
                        <input type="text" name="primary_color" x-model="color" class="form-input w-28 font-mono">
                    </div>
                    @error('primary_color')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="flex gap-2">
                    @foreach (['#4f46e5', '#0ea5e9', '#059669', '#dc2626', '#d97706', '#7c3aed', '#0f172a', '#db2777'] as $c)
                        <button type="button" class="h-8 w-8 rounded-full ring-2 ring-offset-2" style="background: {{ $c }}" :class="color === '{{ $c }}' ? 'ring-slate-400' : 'ring-transparent'" @click="color = '{{ $c }}'"></button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Numeración (prefijos)</h2></div>
                <div class="card-body grid grid-cols-2 gap-4">
                    @foreach (\App\Models\Document::TYPES as $type => $config)
                        <x-form.input :name="'prefix_'.$type" :label="$config['label']" :value="$company->prefix($type)"/>
                    @endforeach
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Textos predeterminados</h2></div>
                <div class="card-body space-y-4">
                    <x-form.textarea name="invoice_terms" label="Términos y condiciones" :value="$company->invoice_terms" rows="3"/>
                    <x-form.textarea name="invoice_notes" label="Nota al cliente" :value="$company->invoice_notes" rows="2"/>
                    <x-form.input name="invoice_footer" label="Pie de página" :value="$company->invoice_footer"/>
                </div>
            </div>
        </div>
        @can('settings.edit')<button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar configuración</button>@endcan
    </form>
</x-app-layout>
