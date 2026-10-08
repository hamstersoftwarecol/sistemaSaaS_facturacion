<x-app-layout title="Tipos impositivos">
    <x-page-header title="Tipos impositivos" subtitle="IVA, impuestos al consumo y otros tributos aplicables a sus productos">
        @can('settings.edit')<a href="{{ route('tax-rates.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo impuesto</a>@endcan
    </x-page-header>
    <div class="card max-w-3xl overflow-x-auto">
        <table class="table">
            <thead><tr><th>Nombre</th><th class="text-right">Tasa</th><th>Predeterminado</th><th>Estado</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($taxRates as $tax)
                    <tr>
                        <td class="font-medium">{{ $tax->name }}</td>
                        <td class="text-right">{{ pct($tax->rate) }}</td>
                        <td>@if ($tax->is_default)<x-badge color="indigo">Predeterminado</x-badge>@endif</td>
                        <td><x-badge :color="$tax->is_active ? 'green' : 'gray'">{{ $tax->is_active ? 'Activo' : 'Inactivo' }}</x-badge></td>
                        <td class="text-right whitespace-nowrap">@can('settings.edit')<a href="{{ route('tax-rates.edit', $tax) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a><x-delete-button :action="route('tax-rates.destroy', $tax)" :small="true"/>@endcan</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">Sin impuestos configurados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
