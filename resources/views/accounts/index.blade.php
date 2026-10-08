<x-app-layout title="Efectivo y bancos">
    <x-page-header title="Efectivo y bancos" subtitle="Cajas, cuentas bancarias y sus movimientos">
        @can('accounts.create')
            <a href="{{ route('accounts.transfer') }}" class="btn-secondary"><x-icon name="switch" class="h-4 w-4"/> Transferencia</a>
            <a href="{{ route('accounts.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nueva cuenta</a>
        @endcan
    </x-page-header>
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Total disponible" :value="money($totalCash + $totalBank)" icon="banknotes" color="green"/>
        <x-stat label="En efectivo / cajas" :value="money($totalCash)" icon="dollar" color="yellow"/>
        <x-stat label="En bancos" :value="money($totalBank)" icon="bank" color="blue"/>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($accounts as $account)
            @php $balance = $account->balance(); @endphp
            <a href="{{ route('accounts.show', $account) }}" class="card block p-5 transition hover:shadow-md {{ $account->is_active ? '' : 'opacity-60' }}">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $account->type === 'bank' ? 'bg-sky-50 text-sky-600' : 'bg-amber-50 text-amber-600' }}">
                            <x-icon :name="$account->type === 'bank' ? 'bank' : 'banknotes'" class="h-5 w-5"/>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">{{ $account->name }}</p>
                            <p class="text-xs text-slate-500">{{ $account->type === 'bank' ? trim($account->bank_name.' '.$account->account_number) : 'Caja'.($account->branch ? ' · '.$account->branch->name : '') }}</p>
                        </div>
                    </div>
                    @unless ($account->is_active)<x-badge>Inactiva</x-badge>@endunless
                </div>
                <p class="mt-5 text-xs uppercase tracking-wide text-slate-400">Saldo actual</p>
                <p class="text-2xl font-bold {{ $balance < 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ money($balance) }}</p>
            </a>
        @empty
            <div class="card md:col-span-3"><x-empty-state icon="bank" title="Sin cuentas" description="Cree una caja o cuenta bancaria para registrar cobros y pagos."/></div>
        @endforelse
    </div>
</x-app-layout>
