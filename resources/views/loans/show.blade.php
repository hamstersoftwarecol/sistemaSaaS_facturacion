<x-app-layout :title="'Préstamo · '.$loan->party_name">
    <x-page-header :title="$loan->party_name" :subtitle="\App\Models\Loan::TYPES[$loan->type].' · '.num($loan->interest_rate).'% anual · '.\App\Models\Loan::FREQUENCIES[$loan->frequency]" :back="route('loans.index')">
        @can('loans.edit')<a href="{{ route('loans.edit', $loan) }}" class="btn-secondary"><x-icon name="pencil" class="h-4 w-4"/> Editar</a>@endcan
        @can('loans.delete')<x-delete-button :action="route('loans.destroy', $loan)" confirm="Se eliminarán el préstamo, sus cuotas y los movimientos de caja asociados. ¿Continuar?"/>@endcan
    </x-page-header>
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Capital" :value="money($loan->principal)" icon="banknotes"/>
        <x-stat label="Total con intereses" :value="money($loan->totalAmount())" icon="calculator" color="purple"/>
        <x-stat label="Pagado" :value="money($loan->paidAmount())" icon="check" color="green"/>
        <x-stat label="Pendiente" :value="money($loan->outstanding())" icon="clock" color="yellow"/>
    </div>
    <div class="card overflow-x-auto">
        <div class="card-header"><h2 class="card-title">Cuadro de amortización</h2><x-badge :color="$loan->statusColor()">{{ $loan->statusLabel() }}</x-badge></div>
        <table class="table">
            <thead><tr><th>N.º</th><th>Vencimiento</th><th class="text-right">Capital</th><th class="text-right">Interés</th><th class="text-right">Cuota</th><th class="text-right">Saldo</th><th>Estado</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($loan->installments as $inst)
                    <tr class="{{ $inst->status === 'paid' ? 'text-slate-400' : '' }}">
                        <td>{{ $inst->number }}</td>
                        <td class="{{ $inst->isOverdue() ? 'font-semibold text-rose-600' : '' }}">{{ fdate($inst->due_date) }}</td>
                        <td class="text-right">{{ money($inst->principal) }}</td>
                        <td class="text-right">{{ money($inst->interest) }}</td>
                        <td class="text-right font-semibold">{{ money($inst->amount) }}</td>
                        <td class="text-right">{{ money($inst->balance_after) }}</td>
                        <td>
                            @if ($inst->status === 'paid')<x-badge color="green">Pagada {{ fdate($inst->paid_at) }}</x-badge>
                            @elseif ($inst->isOverdue())<x-badge color="red">Vencida</x-badge>
                            @else<x-badge color="yellow">Pendiente</x-badge>@endif
                        </td>
                        <td class="whitespace-nowrap text-right">
                            @can('loans.edit')
                                @if ($inst->status === 'paid')
                                    <form method="POST" action="{{ route('loans.installments.unpay', $inst) }}" onsubmit="return confirm('¿Revertir el pago de esta cuota?')">@csrf<button class="btn-ghost btn-sm">Revertir</button></form>
                                @elseif ($loan->status === 'active')
                                    <form method="POST" action="{{ route('loans.installments.pay', $inst) }}" class="inline-flex items-center gap-1">
                                        @csrf
                                        <input type="hidden" name="paid_at" value="{{ today()->toDateString() }}">
                                        <select name="account_id" class="form-select py-1 text-xs" required>
                                            @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($a->id === $loan->account_id)>{{ $a->name }}</option>@endforeach
                                        </select>
                                        <button class="btn-success btn-sm">{{ $loan->type === 'borrowed' ? 'Pagar' : 'Cobrar' }}</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
