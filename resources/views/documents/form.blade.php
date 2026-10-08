@php
    $party = $config['party'];
    $partyKey = $party.'_id';
    $isEdit = $document->exists;
    $title = $isEdit ? 'Editar '.mb_strtolower($config['label']).' '.$document->number : \App\Models\Document::phrase($type, 'new');
    $editorConfig = [
        'items' => old('items', $items),
        'products' => $products,
        'taxRates' => $taxRates->map(fn ($t) => ['id' => $t->id, 'rate' => (float) $t->rate, 'name' => $t->label()])->values(),
        'priceLists' => $priceLists,
        'parties' => $parties,
        'partyId' => old($partyKey, $document->{$partyKey}),
        'priceListId' => old('price_list_id', $document->price_list_id),
        'defaultTaxRateId' => $defaultTaxRateId,
        'calculateUrl' => route($config['route'].'.calculate'),
        'onPartyTerms' => in_array($type, ['invoice', 'purchase_order']) && ! $isEdit,
    ];
    $parentLabel = $parentType ? \App\Models\Document::config($parentType, 'label') : null;
@endphp
<x-app-layout :title="$title">
    <x-page-header :title="$title" :back="$isEdit ? route($config['route'].'.show', $document) : route($config['route'].'.index')"/>

    <form method="POST" action="{{ $isEdit ? route($config['route'].'.update', $document) : route($config['route'].'.store') }}"
          x-data="documentEditor(@js($editorConfig))" @keydown.ctrl.enter.prevent="$el.submit()">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="space-y-6">
                <div class="card card-body">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="sm:col-span-2 lg:col-span-1">
                            <label class="form-label">{{ $party === 'customer' ? 'Cliente' : 'Proveedor' }} <span class="text-rose-500">*</span></label>
                            <select name="{{ $partyKey }}" class="form-select" x-model="partyId" @change="onPartyChange()" required>
                                <option value="">Seleccione…</option>
                                @foreach ($parties as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}{{ $p->tax_id ? ' · '.$p->tax_id : '' }}</option>
                                @endforeach
                            </select>
                            @error($partyKey)<p class="form-error">{{ $message }}</p>@enderror
                            @can($party === 'customer' ? 'customers.create' : 'suppliers.create')
                                <a href="{{ route($party === 'customer' ? 'customers.create' : 'suppliers.create') }}" target="_blank" class="mt-1 inline-block text-xs link">+ Nuevo {{ $party === 'customer' ? 'cliente' : 'proveedor' }}</a>
                            @endcan
                        </div>
                        <x-form.input name="date" type="date" label="Fecha" :value="$document->date" required x-ref="date"/>
                        @if (! in_array($type, ['credit_note', 'debit_note', 'delivery_note']))
                            <x-form.input name="due_date" type="date" :label="$type === 'quote' ? 'Válido hasta' : ($type === 'purchase_order' ? 'Fecha de pago' : 'Vencimiento')" :value="$document->due_date" x-ref="dueDate"/>
                        @endif
                        <x-form.input name="reference" label="Referencia" :value="$document->reference" placeholder="Pedido, orden del cliente…"/>
                        @if ($party === 'customer')
                            <div>
                                <label class="form-label">Lista de precios</label>
                                <select name="price_list_id" class="form-select" x-model="priceListId">
                                    <option value="">Precio base</option>
                                    @foreach ($priceLists as $id => $list)
                                        <option value="{{ $id }}">{{ $list['name'] }}</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">Se aplica al seleccionar productos.</p>
                            </div>
                        @endif
                        @if (in_array($type, ['quote', 'delivery_note', 'purchase_order']))
                            <x-form.select name="status" label="Estado" :value="$document->status"
                                :options="collect($config['statuses'])->except(['converted', 'invoiced'])->map(fn ($s) => $s[0])->all()"/>
                        @endif
                        @if ($parentType)
                            <div>
                                <label class="form-label">{{ $parentLabel }} relacionada</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">— Ninguna —</option>
                                    @foreach ($parents as $parent)
                                        <option value="{{ $parent->id }}" x-show="!partyId || String(partyId) === '{{ $parent->customer_id ?? $parent->supplier_id }}'"
                                            @selected((string) old('parent_id', $document->parent_id) === (string) $parent->id)>{{ $parent->number }} · {{ money($parent->total) }}</option>
                                    @endforeach
                                </select>
                                <p class="form-hint">{{ in_array($type, ['credit_note', 'debit_note']) ? 'El importe se descontará del saldo de este documento.' : 'Opcional.' }}</p>
                            </div>
                        @endif
                    </div>
                    @if (in_array($type, ['credit_note', 'debit_note']))
                        <x-form.checkbox name="affects_stock" class="mt-4" :checked="$document->affects_stock ?? true"
                            :label="$type === 'credit_note' ? 'Reingresar la mercancía al inventario' : 'Descontar la mercancía devuelta del inventario'"
                            hint="Desmarque si la nota corresponde solo a un ajuste de precio o descuento."/>
                    @else
                        <input type="hidden" name="affects_stock" value="1">
                    @endif
                </div>

                @include('documents.partials.items-editor')

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="card card-body space-y-4 self-start xl:col-span-2">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-form.textarea name="notes" label="Notas para el {{ $party === 'customer' ? 'cliente' : 'proveedor' }}" :value="$document->notes"/>
                        <x-form.textarea name="terms" label="Términos y condiciones" :value="$document->terms"/>
                    </div>
                    @if ($customFields->isNotEmpty())
                        <div class="border-t border-slate-100 pt-4">
                            <h3 class="mb-3 text-sm font-semibold text-slate-700">Campos personalizados</h3>
                            <x-custom-fields :fields="$customFields" :values="$document->custom_fields ?? []"/>
                        </div>
                    @endif
                </div>

            <div class="space-y-4">
                @include('documents.partials.totals')
                <div class="card card-body space-y-2">
                    <button type="submit" class="btn-primary w-full py-2.5"><x-icon name="check" class="h-4 w-4"/> {{ $isEdit ? 'Guardar cambios' : 'Crear '.mb_strtolower($config['label']) }}</button>
                    <a href="{{ $isEdit ? route($config['route'].'.show', $document) : route($config['route'].'.index') }}" class="btn-secondary w-full">Cancelar</a>
                    <p class="pt-1 text-center text-xs text-slate-400">Atajo: <kbd class="rounded border px-1">Ctrl</kbd> + <kbd class="rounded border px-1">Enter</kbd> para guardar</p>
                </div>
                @if ($type === 'invoice')
                    <div class="rounded-lg bg-indigo-50 p-4 text-xs text-indigo-800">
                        <p class="font-semibold">Verificación QR</p>
                        <p class="mt-1">Cada factura incluye un código QR único para que su cliente verifique la autenticidad del documento en línea.</p>
                    </div>
                @endif
            </div>
            </div>
        </div>
    </form>
</x-app-layout>
