@php $module = $meta['route'] === 'payments-received' ? 'payments_received' : 'payments_made'; @endphp
<x-app-layout :title="$meta['label'].' '.$payment->number">
    <x-page-header :title="$meta['label'].' '.$payment->number" :back="route($meta['route'].'.index')">
        <a href="{{ route($meta['route'].'.receipt', $payment) }}" target="_blank" class="btn-secondary"><x-icon name="printer" class="h-4 w-4"/> Recibo</a>
        @can($module.'.edit')<a href="{{ route($meta['route'].'.edit', $payment) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>@endcan
        @can($module.'.delete')<x-delete-button :action="route($meta['route'].'.destroy', $payment)" confirm="¿Eliminar este pago? Se revertirá el saldo del documento y el movimiento de caja."/>@endcan
    </x-page-header>

    <div class="grid max-w-4xl grid-cols-1 gap-6 md:grid-cols-3">
        <div class="card card-body md:col-span-2">
            <dl class="grid grid-cols-2 gap-5 text-sm">
                <div><dt class="text-slate-400">{{ $meta['party_label'] }}</dt><dd class="font-semibold">{{ $payment->party()?->name }}</dd></div>
                <div><dt class="text-slate-400">Fecha</dt><dd class="font-semibold">{{ fdate($payment->date) }}</dd></div>
                <div><dt class="text-slate-400">Método</dt><dd class="font-semibold">{{ $payment->methodLabel() }}</dd></div>
                <div><dt class="text-slate-400">Cuenta</dt><dd class="font-semibold">{{ $payment->account?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">{{ $meta['doc_label'] }}</dt><dd class="font-semibold">
                    @if ($payment->document)<a class="link" href="{{ route($payment->document->routeName('show'), $payment->document) }}">{{ $payment->document->number }}</a>@else Pago a cuenta @endif
                </dd></div>
                <div><dt class="text-slate-400">Referencia</dt><dd class="font-semibold">{{ $payment->reference ?: '—' }}</dd></div>
                <div><dt class="text-slate-400">Registrado por</dt><dd class="font-semibold">{{ $payment->user?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-400">Sucursal</dt><dd class="font-semibold">{{ $payment->branch?->name }}</dd></div>
                @if ($payment->notes)<div class="col-span-2"><dt class="text-slate-400">Notas</dt><dd>{{ $payment->notes }}</dd></div>@endif
            </dl>
        </div>
        <div class="card card-body bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
            <p class="text-sm opacity-80">Importe</p>
            <p class="mt-1 text-3xl font-bold">{{ money($payment->amount) }}</p>
            @if ($payment->document)
                <p class="mt-4 text-sm opacity-80">Saldo restante del documento</p>
                <p class="text-lg font-semibold">{{ money($payment->document->balance) }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
