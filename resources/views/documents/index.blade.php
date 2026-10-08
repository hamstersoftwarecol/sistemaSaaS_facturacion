<x-app-layout :title="$config['plural']">
    <x-page-header :title="$config['plural']" :subtitle="'Gestione sus '.mb_strtolower($config['plural'])">
        @if ($type === 'invoice')
            @can('invoices.create')
                <a href="{{ route('invoices.quick') }}" class="btn-secondary"><x-icon name="bolt" class="h-4 w-4 text-amber-500"/> Venta rápida</a>
            @endcan
        @endif
        @can($config['module'].'.create')
            <a href="{{ route($config['route'].'.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> {{ \App\Models\Document::phrase($type, 'new') }}</a>
        @endcan
    </x-page-header>

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat label="Documentos" :value="number_format($summary->count)" icon="document" color="indigo"/>
        <x-stat label="Importe total" :value="money($summary->total)" icon="banknotes" color="green"/>
        @if (in_array($type, ['invoice', 'purchase_order']))
            <x-stat label="Saldo pendiente" :value="money($summary->balance)" icon="clock" color="yellow"/>
        @else
            <x-stat label="Promedio" :value="money($summary->count ? $summary->total / $summary->count : 0)" icon="chart" color="blue"/>
        @endif
    </div>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4">
            <div class="min-w-[220px] flex-1">
                <label class="form-label">Buscar</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Número, referencia o {{ $config['party'] === 'customer' ? 'cliente' : 'proveedor' }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Estado</label>
                <select name="status" class="form-select w-40">
                    <option value="">Todos</option>
                    @foreach ($config['statuses'] as $key => [$label])
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                    @if ($type === 'invoice')
                        <option value="overdue" @selected(request('status') === 'overdue')>Vencidas</option>
                    @endif
                </select>
            </div>
            <div><label class="form-label">Desde</label><input type="date" name="from" value="{{ request('from') }}" class="form-input"></div>
            <div><label class="form-label">Hasta</label><input type="date" name="to" value="{{ request('to') }}" class="form-input"></div>
            <button class="btn-secondary"><x-icon name="search" class="h-4 w-4"/> Filtrar</button>
            @if (request()->hasAny(['q', 'status', 'from', 'to']))
                <a href="{{ route($config['route'].'.index') }}" class="btn-ghost">Limpiar</a>
            @endif
        </form>

        @if ($documents->isEmpty())
            <x-empty-state :icon="$config['icon']" :title="'No hay '.mb_strtolower($config['plural'])" description="Cuando cree documentos aparecerán aquí.">
                @can($config['module'].'.create')
                    <a href="{{ route($config['route'].'.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Crear el primero</a>
                @endcan
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>{{ $config['party'] === 'customer' ? 'Cliente' : 'Proveedor' }}</th>
                            <th>Fecha</th>
                            @if (in_array($type, ['invoice', 'quote', 'purchase_order']))<th>{{ $type === 'quote' ? 'Válido hasta' : 'Vence' }}</th>@endif
                            <th>Sucursal</th>
                            <th class="text-right">Total</th>
                            @if (in_array($type, ['invoice', 'purchase_order']))<th class="text-right">Saldo</th>@endif
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($documents as $document)
                            <tr>
                                <td><a href="{{ route($config['route'].'.show', $document) }}" class="link">{{ $document->number }}</a>
                                    @if ($document->reference)<div class="text-xs text-slate-400">{{ $document->reference }}</div>@endif
                                </td>
                                <td class="max-w-[220px] truncate">{{ $document->party()?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ fdate($document->date) }}</td>
                                @if (in_array($type, ['invoice', 'quote', 'purchase_order']))<td class="whitespace-nowrap {{ $document->isOverdue() ? 'text-rose-600 font-medium' : '' }}">{{ fdate($document->due_date) }}</td>@endif
                                <td class="text-slate-500">{{ $document->branch?->name }}</td>
                                <td class="text-right font-medium whitespace-nowrap">{{ money($document->total) }}</td>
                                @if (in_array($type, ['invoice', 'purchase_order']))<td class="text-right whitespace-nowrap {{ $document->balance > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ money($document->balance) }}</td>@endif
                                <td><x-badge :color="$document->statusColor()">{{ $document->statusLabel() }}</x-badge></td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="{{ route($config['route'].'.print', $document) }}" target="_blank" class="btn-ghost btn-sm" title="Imprimir"><x-icon name="printer" class="h-4 w-4"/></a>
                                    <a href="{{ route($config['route'].'.pdf', $document) }}" target="_blank" class="btn-ghost btn-sm" title="PDF"><x-icon name="download" class="h-4 w-4"/></a>
                                    @can($config['module'].'.edit')
                                        <a href="{{ route($config['route'].'.edit', $document) }}" class="btn-ghost btn-sm" title="Editar"><x-icon name="pencil" class="h-4 w-4"/></a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $documents->links() }}</div>
        @endif
    </div>
</x-app-layout>
