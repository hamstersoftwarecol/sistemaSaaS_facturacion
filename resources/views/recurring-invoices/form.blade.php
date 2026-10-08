@php
    $isEdit = $recurring->exists;
    $editorConfig = [
        'items' => old('items', $recurring->items ?? []),
        'products' => $products,
        'taxRates' => $taxRates->map(fn ($t) => ['id' => $t->id, 'rate' => (float) $t->rate, 'name' => $t->label()])->values(),
        'priceLists' => [],
        'parties' => $customers,
        'partyId' => old('customer_id', $recurring->customer_id),
        'defaultTaxRateId' => $defaultTaxRateId,
        'calculateUrl' => route('invoices.calculate'),
    ];
@endphp
<x-app-layout :title="$isEdit ? 'Editar factura recurrente' : 'Nueva factura recurrente'">
    <x-page-header :title="$isEdit ? 'Editar '.$recurring->title : 'Nueva factura recurrente'" :back="route('recurring-invoices.index')"/>
    <form method="POST" action="{{ $isEdit ? route('recurring-invoices.update', $recurring) : route('recurring-invoices.store') }}" x-data="documentEditor(@js($editorConfig))">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <div class="space-y-6">
                <div class="card card-body grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-form.input name="title" label="Título / concepto" :value="$recurring->title" required placeholder="Mantenimiento mensual" class="sm:col-span-2 lg:col-span-3"/>
                    <div>
                        <label class="form-label">Cliente *</label>
                        <select name="customer_id" class="form-select" x-model="partyId" required>
                            <option value="">Seleccione…</option>
                            @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                        </select>
                        @error('customer_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <x-form.select name="frequency" label="Frecuencia" :options="\App\Models\RecurringInvoice::FREQUENCIES" :value="$recurring->frequency" required/>
                    <x-form.input name="interval" type="number" min="1" label="Cada (intervalo)" :value="$recurring->interval" required hint="Ej.: 2 con mensual = cada 2 meses"/>
                    <x-form.input name="due_days" type="number" min="0" label="Días para pagar" :value="$recurring->due_days" required/>
                    <x-form.input name="start_date" type="date" label="Primera emisión" :value="$recurring->start_date" required/>
                    <x-form.input name="end_date" type="date" label="Finaliza (opcional)" :value="$recurring->end_date"/>
                    <x-form.input name="max_occurrences" type="number" min="1" label="N.º máx. de facturas" :value="$recurring->max_occurrences"/>
                    <x-form.select name="status" label="Estado" :options="collect(\App\Models\RecurringInvoice::STATUSES)->map(fn ($s) => $s[0])->all()" :value="$recurring->status"/>
                </div>
                @include('documents.partials.items-editor')
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="card card-body grid grid-cols-1 gap-4 sm:grid-cols-2 xl:col-span-2">
                    <x-form.textarea name="notes" label="Notas" :value="$recurring->notes"/>
                    <x-form.textarea name="terms" label="Términos" :value="$recurring->terms"/>
                </div>
                <div class="space-y-4">
                    @include('documents.partials.totals')
                    <button class="btn-primary w-full py-2.5"><x-icon name="check" class="h-4 w-4"/> Guardar plantilla</button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
