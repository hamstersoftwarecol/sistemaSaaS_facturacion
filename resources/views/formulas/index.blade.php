<x-app-layout title="Fórmulas empresariales">
    <x-page-header title="Fórmulas empresariales" subtitle="Recargos, retenciones y cálculos automáticos que se aplican a los totales de sus documentos">
        @can('settings.edit')<a href="{{ route('formulas.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nueva fórmula</a>@endcan
    </x-page-header>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            @if ($formulas->isEmpty())
                <x-empty-state icon="calculator" title="Sin fórmulas" description="Ejemplos: retención en la fuente (subtotal * 0.025), recargo por envío (if(subtotal < 100, 10, 0)), propina, ICA…"/>
            @else
                <table class="table">
                    <thead><tr><th>Nombre</th><th>Expresión</th><th>Aplica a</th><th>Acción</th><th>Estado</th><th></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($formulas as $formula)
                            <tr>
                                <td><p class="font-medium">{{ $formula->name }}</p><code class="text-xs text-slate-400">{{ $formula->code }}</code></td>
                                <td><code class="rounded bg-slate-100 px-2 py-1 text-xs">{{ $formula->expression }}</code></td>
                                <td>{{ \App\Models\Formula::APPLIES_TO[$formula->applies_to] }}</td>
                                <td><x-badge :color="['add' => 'green', 'subtract' => 'red', 'info' => 'gray'][$formula->action]">{{ ['add' => 'Suma', 'subtract' => 'Resta', 'info' => 'Informativa'][$formula->action] }}</x-badge></td>
                                <td><x-badge :color="$formula->is_active ? 'green' : 'gray'">{{ $formula->is_active ? 'Activa' : 'Inactiva' }}</x-badge></td>
                                <td class="text-right whitespace-nowrap">@can('settings.edit')<a href="{{ route('formulas.edit', $formula) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a><x-delete-button :action="route('formulas.destroy', $formula)" :small="true"/>@endcan</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @include('formulas._help')
    </div>
</x-app-layout>
