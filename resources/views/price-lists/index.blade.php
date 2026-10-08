<x-app-layout title="Listas de precios">
    <x-page-header title="Listas de precios" subtitle="Precios especiales para mayoristas, distribuidores o clientes VIP">
        @can('price_lists.create')<a href="{{ route('price-lists.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nueva lista</a>@endcan
    </x-page-header>
    @if ($priceLists->isEmpty())
        <div class="card"><x-empty-state icon="tag" title="Sin listas de precios" description="Cree listas con ajustes porcentuales o precios fijos y asígnelas a sus clientes."/></div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($priceLists as $list)
                <a href="{{ route('price-lists.show', $list) }}" class="card card-body block transition hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <h3 class="font-semibold text-slate-900">{{ $list->name }}</h3>
                        <x-badge :color="$list->is_active ? 'green' : 'gray'">{{ $list->is_active ? 'Activa' : 'Inactiva' }}</x-badge>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $list->description }}</p>
                    <div class="mt-4 flex gap-6 text-sm">
                        <div><p class="text-xs text-slate-400">Ajuste base</p><p class="font-semibold {{ $list->percentage < 0 ? 'text-emerald-600' : 'text-slate-800' }}">{{ $list->percentage > 0 ? '+' : '' }}{{ num($list->percentage) }}%</p></div>
                        <div><p class="text-xs text-slate-400">Precios fijos</p><p class="font-semibold">{{ $list->items_count }}</p></div>
                        <div><p class="text-xs text-slate-400">Clientes</p><p class="font-semibold">{{ $list->customers_count }}</p></div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
