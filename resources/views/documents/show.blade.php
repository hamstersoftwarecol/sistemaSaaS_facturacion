@php
    $party = $document->party();
    $partyRoute = $config['party'] === 'customer' ? 'customers.show' : 'suppliers.show';
    $route = $config['route'];
    $canEdit = auth()->user()->can($config['module'].'.edit');
@endphp
<x-app-layout :title="$config['label'].' '.$document->number">
    <x-page-header :title="$config['label'].' '.$document->number" :back="route($route.'.index')">
        <a href="{{ route($route.'.print', $document) }}" target="_blank" class="btn-secondary"><x-icon name="printer" class="h-4 w-4"/> Imprimir</a>
        <x-dropdown align="right" width="w-56">
            <x-slot name="trigger">
                <button type="button" class="btn-secondary"><x-icon name="download" class="h-4 w-4"/> PDF <x-icon name="chevron-down" class="h-4 w-4"/></button>
            </x-slot>
            <x-slot name="content">
                <p class="px-4 pb-1 pt-2 text-xs font-semibold uppercase text-slate-400">Estilo de diseño</p>
                @foreach (\App\Models\Company::INVOICE_STYLES as $style => $label)
                    <x-dropdown-link :href="route($route.'.pdf', [$document, 'style' => $style, 'download' => 1])">{{ $label }}{{ company()->invoice_style === $style ? ' (predeterminado)' : '' }}</x-dropdown-link>
                @endforeach
            </x-slot>
        </x-dropdown>
        @can($config['module'].'.create')
            <form method="POST" action="{{ route($route.'.duplicate', $document) }}">@csrf<button class="btn-secondary"><x-icon name="duplicate" class="h-4 w-4"/> Duplicar</button></form>
        @endcan
        @if ($canEdit && $document->status !== 'cancelled')
            <a href="{{ route($route.'.edit', $document) }}" class="btn-primary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>
        @endif
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-body grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $config['party'] === 'customer' ? 'Cliente' : 'Proveedor' }}</p>
                        @if ($party)
                            <a href="{{ route($partyRoute, $party) }}" class="mt-1 block font-semibold text-slate-900 hover:text-indigo-600">{{ $party->name }}</a>
                            <p class="text-sm text-slate-500">{{ $party->tax_id }}</p>
                            <p class="text-sm text-slate-500">{{ $party->email }}</p>
                            <p class="text-sm text-slate-500">{{ collect([$party->address, $party->city])->filter()->implode(', ') }}</p>
                        @else
                            <p class="mt-1 text-sm text-slate-400">—</p>
                        @endif
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:col-span-2">
                        <div><dt class="text-slate-400">Estado</dt><dd class="mt-0.5"><x-badge :color="$document->statusColor()">{{ $document->statusLabel() }}</x-badge></dd></div>
                        <div><dt class="text-slate-400">Fecha</dt><dd class="font-medium">{{ fdate($document->date) }}</dd></div>
                        @if ($document->due_date)<div><dt class="text-slate-400">{{ $document->type === 'quote' ? 'Válido hasta' : 'Vencimiento' }}</dt><dd class="font-medium {{ $document->isOverdue() ? 'text-rose-600' : '' }}">{{ fdate($document->due_date) }}</dd></div>@endif
                        @if ($document->reference)<div><dt class="text-slate-400">Referencia</dt><dd class="font-medium">{{ $document->reference }}</dd></div>@endif
                        <div><dt class="text-slate-400">Sucursal</dt><dd class="font-medium">{{ $document->branch?->name }}</dd></div>
                        <div><dt class="text-slate-400">Creado por</dt><dd class="font-medium">{{ $document->user?->name ?? 'Sistema' }}</dd></div>
                        @if ($document->type === 'purchase_order' && $document->status === 'received')
                            <div><dt class="text-slate-400">Pago</dt><dd class="font-medium">{{ $document->paymentStatusLabel() }}</dd></div>
                        @endif
                        @if (in_array($document->type, ['credit_note', 'debit_note']))
                            <div><dt class="text-slate-400">Inventario</dt><dd class="font-medium">{{ $document->affects_stock ? 'Afecta existencias' : 'Sin movimiento de stock' }}</dd></div>
                        @endif
                    </dl>
                </div>

                <div class="overflow-x-auto border-t border-slate-100">
                    <table class="table">
                        <thead>
                            <tr><th>Descripción</th><th class="text-right">Cant.</th><th class="text-right">Precio</th><th class="text-right">Desc.</th><th class="text-right">Imp.</th><th class="text-right">Total</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($document->items as $item)
                                <tr>
                                    <td>
                                        <p class="font-medium text-slate-800">{{ $item->description }}</p>
                                        @if ($item->product)<p class="text-xs text-slate-400">{{ $item->product->sku }}</p>@endif
                                    </td>
                                    <td class="text-right whitespace-nowrap">{{ qty($item->quantity) }} {{ $item->unit }}</td>
                                    <td class="text-right whitespace-nowrap">{{ money($item->unit_price) }}</td>
                                    <td class="text-right">{{ (float) $item->discount_percent > 0 ? pct($item->discount_percent) : '—' }}</td>
                                    <td class="text-right">{{ pct($item->tax_rate) }}</td>
                                    <td class="text-right font-medium whitespace-nowrap">{{ money($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end border-t border-slate-100 p-5">
                    <dl class="w-full max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>{{ money($document->subtotal) }}</dd></div>
                        @if ((float) $document->discount_total > 0)<div class="flex justify-between"><dt class="text-slate-500">Descuentos incluidos</dt><dd class="text-slate-500">-{{ money($document->discount_total) }}</dd></div>@endif
                        <div class="flex justify-between"><dt class="text-slate-500">Impuestos</dt><dd>{{ money($document->tax_total) }}</dd></div>
                        @foreach ($document->adjustments ?? [] as $adj)
                            <div class="flex justify-between"><dt class="text-slate-500">{{ $adj['name'] }}</dt><dd class="{{ $adj['action'] === 'subtract' ? 'text-rose-600' : '' }}">{{ $adj['action'] === 'subtract' ? '-' : '' }}{{ money($adj['amount']) }}</dd></div>
                        @endforeach
                        <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><dt>Total</dt><dd>{{ money($document->total) }}</dd></div>
                        @if (in_array($document->type, ['invoice', 'purchase_order']))
                            <div class="flex justify-between text-emerald-600"><dt>Pagado / acreditado</dt><dd>{{ money($document->amount_paid) }}</dd></div>
                            <div class="flex justify-between font-semibold {{ $document->balance > 0 ? 'text-amber-600' : 'text-slate-500' }}"><dt>Saldo pendiente</dt><dd>{{ money($document->balance) }}</dd></div>
                        @endif
                    </dl>
                </div>
            </div>

            @if ($document->notes || $document->terms || collect($document->custom_fields)->filter()->isNotEmpty())
                <div class="card card-body grid grid-cols-1 gap-6 text-sm sm:grid-cols-2">
                    @if ($document->notes)<div><p class="mb-1 font-semibold text-slate-700">Notas</p><p class="whitespace-pre-line text-slate-600">{{ $document->notes }}</p></div>@endif
                    @if ($document->terms)<div><p class="mb-1 font-semibold text-slate-700">Términos</p><p class="whitespace-pre-line text-slate-600">{{ $document->terms }}</p></div>@endif
                    @foreach ($customFields as $field)
                        @if (filled($document->custom_fields[$field->name] ?? null))
                            <div><p class="mb-1 font-semibold text-slate-700">{{ $field->label }}</p><p class="text-slate-600">{{ $field->displayValue($document->custom_fields[$field->name]) }}</p></div>
                        @endif
                    @endforeach
                </div>
            @endif

            @if (in_array($document->type, ['invoice', 'purchase_order']))
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Pagos registrados</h2>
                        @php $payRoute = $document->type === 'invoice' ? 'payments-received' : 'payments-made'; $payModule = $document->type === 'invoice' ? 'payments_received' : 'payments_made'; @endphp
                        @if ($document->isPayable() && $document->balance > 0)
                            @can($payModule.'.create')
                                <a href="{{ route($payRoute.'.create', ['document_id' => $document->id]) }}" class="btn-success btn-sm"><x-icon name="plus" class="h-4 w-4"/> Registrar pago</a>
                            @endcan
                        @endif
                    </div>
                    @if ($document->payments->isEmpty())
                        <p class="p-5 text-sm text-slate-500">Aún no hay pagos para este documento.</p>
                    @else
                        <table class="table">
                            <thead><tr><th>Recibo</th><th>Fecha</th><th>Método</th><th>Cuenta</th><th class="text-right">Importe</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($document->payments as $payment)
                                    <tr>
                                        <td><a class="link" href="{{ route($payRoute.'.show', $payment) }}">{{ $payment->number }}</a></td>
                                        <td>{{ fdate($payment->date) }}</td>
                                        <td>{{ $payment->methodLabel() }}</td>
                                        <td>{{ $payment->account?->name }}</td>
                                        <td class="text-right font-medium">{{ money($payment->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card card-body">
                <h2 class="card-title mb-3">Acciones</h2>
                <div class="space-y-2">
                    @foreach ($conversions as $target)
                        @php $targetConfig = \App\Models\Document::config($target); @endphp
                        @can($targetConfig['module'].'.create')
                            @if ($document->status !== 'cancelled')
                                <form method="POST" action="{{ route($route.'.convert', [$document, $target]) }}">
                                    @csrf
                                    <button class="btn-secondary w-full justify-start"><x-icon :name="$targetConfig['icon']" class="h-4 w-4"/> Generar {{ mb_strtolower($targetConfig['label']) }}</button>
                                </form>
                            @endif
                        @endcan
                    @endforeach

                    @if ($canEdit)
                        @php
                            $transitions = match ($document->type) {
                                'quote' => ['sent' => 'Marcar como enviado', 'accepted' => 'Marcar como aceptado', 'rejected' => 'Marcar como rechazado'],
                                'delivery_note' => ['delivered' => 'Marcar como entregado'],
                                'purchase_order' => ['ordered' => 'Marcar como pedida', 'received' => 'Recibir mercancía (entra al inventario)'],
                                default => [],
                            };
                        @endphp
                        @foreach ($transitions as $status => $label)
                            @if ($document->status !== $status && $document->status !== 'cancelled')
                                <form method="POST" action="{{ route($route.'.status', $document) }}">
                                    @csrf <input type="hidden" name="status" value="{{ $status }}">
                                    <button class="btn-secondary w-full justify-start"><x-icon name="check" class="h-4 w-4"/> {{ $label }}</button>
                                </form>
                            @endif
                        @endforeach
                        @if (array_key_exists('cancelled', $config['statuses']) && $document->status !== 'cancelled')
                            <form method="POST" action="{{ route($route.'.status', $document) }}" onsubmit="return confirm('¿Anular este documento? Se revertirán sus efectos en inventario.')">
                                @csrf <input type="hidden" name="status" value="cancelled">
                                <button class="btn-secondary w-full justify-start text-rose-600"><x-icon name="x" class="h-4 w-4"/> Anular documento</button>
                            </form>
                        @endif
                    @endif

                    @can($config['module'].'.delete')
                        <x-delete-button :action="route($route.'.destroy', $document)" label="Eliminar documento" class="w-full justify-start"/>
                    @endcan
                </div>
            </div>

            <div class="card card-body text-center">
                <h2 class="card-title mb-3 text-left">Verificación QR</h2>
                <div class="mx-auto w-32">{!! $qr !!}</div>
                <p class="mt-3 text-xs text-slate-500">Escaneando el código, cualquier persona puede verificar la autenticidad del documento.</p>
                <a href="{{ $document->verificationUrl() }}" target="_blank" class="mt-2 inline-block text-xs link break-all">Abrir página de verificación</a>
            </div>

            @if ($document->parent || $document->children->isNotEmpty())
                <div class="card card-body">
                    <h2 class="card-title mb-3">Documentos relacionados</h2>
                    <ul class="space-y-2 text-sm">
                        @if ($document->parent)
                            <li class="flex items-center justify-between">
                                <span class="text-slate-500">Origen: {{ $document->parent->cfg('label') }}</span>
                                <a class="link" href="{{ route($document->parent->routeName('show'), $document->parent) }}">{{ $document->parent->number }}</a>
                            </li>
                        @endif
                        @foreach ($document->children as $child)
                            <li class="flex items-center justify-between">
                                <span class="text-slate-500">{{ $child->cfg('label') }}</span>
                                <a class="link" href="{{ route($child->routeName('show'), $child) }}">{{ $child->number }} · {{ money($child->total) }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
