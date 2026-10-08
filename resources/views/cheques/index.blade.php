<x-app-layout title="Cheques">
    <x-page-header title="Cheques" subtitle="Cartera de cheques recibidos y emitidos">
        @can('cheques.create')
            <a href="{{ route('cheques.create', ['type' => 'issued']) }}" class="btn-secondary"><x-icon name="send" class="h-4 w-4"/> Cheque emitido</a>
            <a href="{{ route('cheques.create', ['type' => 'received']) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Cheque recibido</a>
        @endcan
    </x-page-header>
    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Por cobrar (en cartera)" :value="money($pendingReceived)" icon="inbox" color="green"/>
        <x-stat label="Emitidos sin compensar" :value="money($pendingIssued)" icon="send" color="red"/>
        <x-stat label="Vencen en 7 días" :value="$dueSoon" icon="clock" color="yellow"/>
    </div>
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="min-w-[200px] flex-1"><label class="form-label">Buscar</label><input type="search" name="q" value="{{ request('q') }}" class="form-input" placeholder="Número, banco o titular"></div>
            <div><label class="form-label">Tipo</label><select name="type" class="form-select w-48"><option value="">Todos</option>@foreach (\App\Models\Cheque::TYPES as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="form-label">Estado</label><select name="status" class="form-select w-44"><option value="">Todos</option>@foreach (\App\Models\Cheque::STATUSES as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select></div>
            <button class="btn-secondary">Filtrar</button>
        </form>
        @if ($cheques->isEmpty())
            <x-empty-state icon="cheque" title="Sin cheques" description="Controle los cheques posfechados y su compensación bancaria."/>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>N.º cheque</th><th>Tipo</th><th>Titular / beneficiario</th><th>Banco</th><th>Emisión</th><th>Cobro</th><th class="text-right">Importe</th><th>Estado</th><th>Acción</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($cheques as $cheque)
                            <tr>
                                <td class="font-mono font-medium">{{ $cheque->cheque_number }}</td>
                                <td><x-badge :color="$cheque->type === 'received' ? 'green' : 'indigo'">{{ $cheque->type === 'received' ? 'Recibido' : 'Emitido' }}</x-badge></td>
                                <td>{{ $cheque->partyName() }}</td>
                                <td>{{ $cheque->bank_name }}</td>
                                <td>{{ fdate($cheque->issue_date) }}</td>
                                <td class="{{ in_array($cheque->status, ['pending', 'deposited']) && $cheque->due_date->lte(today()) ? 'font-semibold text-amber-600' : '' }}">{{ fdate($cheque->due_date) }}</td>
                                <td class="text-right font-semibold">{{ money($cheque->amount) }}</td>
                                <td><x-badge :color="$cheque->statusColor()">{{ $cheque->statusLabel() }}</x-badge></td>
                                <td class="whitespace-nowrap">
                                    @can('cheques.edit')
                                        @if (in_array($cheque->status, ['pending', 'deposited']))
                                            <form method="POST" action="{{ route('cheques.status', $cheque) }}" class="inline-flex items-center gap-1" x-data>
                                                @csrf
                                                <select name="status" class="form-select py-1 text-xs" onchange="this.form.submit()">
                                                    <option value="">Cambiar…</option>
                                                    @if ($cheque->status === 'pending')<option value="deposited">Depositado</option>@endif
                                                    <option value="cleared">Cobrado/compensado</option>
                                                    <option value="bounced">Rechazado</option>
                                                    <option value="cancelled">Anulado</option>
                                                </select>
                                            </form>
                                        @endif
                                        <a href="{{ route('cheques.edit', $cheque) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $cheques->links() }}</div>
        @endif
    </div>
</x-app-layout>
