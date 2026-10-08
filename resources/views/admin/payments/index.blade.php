<x-admin-layout title="Pagos de suscripción">
    <x-page-header title="Pagos de suscripción" subtitle="Verifique los pagos enviados por las empresas y active sus planes">
        <a href="{{ route('admin.payments.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Registrar pago manual</a>
    </x-page-header>
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-stat label="Total aprobado" :value="'$'.number_format($approvedTotal, 2)" icon="check" color="green"/>
        <x-stat label="Pendiente de verificar" :value="'$'.number_format($pendingTotal, 2)" icon="clock" color="yellow"/>
    </div>
    <div class="card">
        <form method="GET" class="flex gap-3 border-b border-slate-100 p-4">
            <select name="status" class="form-select w-48"><option value="">Todos</option>@foreach (\App\Models\SubscriptionPayment::STATUSES as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
            <button class="btn-secondary">Filtrar</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Empresa</th><th>Plan</th><th>Método</th><th>Referencia</th><th class="text-right">Importe</th><th>Estado</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $p)
                        <tr>
                            <td>{{ fdate($p->created_at) }}</td>
                            <td>{{ $p->company?->name }}</td>
                            <td>{{ $p->plan?->name }} <span class="text-xs text-slate-400">{{ \App\Models\Subscription::CYCLES[$p->billing_cycle] ?? '' }}</span></td>
                            <td>{{ $p->paymentMethod?->name ?? 'Manual' }}</td>
                            <td class="text-slate-500">{{ $p->reference }}@if ($p->notes)<div class="text-xs">{{ $p->notes }}</div>@endif</td>
                            <td class="text-right font-semibold">${{ number_format($p->amount, 2) }}</td>
                            <td><x-badge :color="$p->statusColor()">{{ $p->statusLabel() }}</x-badge></td>
                            <td class="whitespace-nowrap text-right">
                                @if ($p->status === 'pending')
                                    <form method="POST" action="{{ route('admin.payments.approve', $p) }}" class="inline">@csrf<button class="btn-success btn-sm">Aprobar</button></form>
                                    <form method="POST" action="{{ route('admin.payments.reject', $p) }}" class="inline">@csrf<button class="btn-secondary btn-sm text-rose-600">Rechazar</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-center text-slate-500">Sin pagos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $payments->links() }}</div>
    </div>
</x-admin-layout>
