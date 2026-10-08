<x-app-layout title="Campos personalizados">
    <x-page-header title="Campos personalizados" subtitle="Añada datos propios de su negocio a clientes, proveedores, productos y documentos">
        @can('settings.edit')<a href="{{ route('custom-fields.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo campo</a>@endcan
    </x-page-header>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @foreach (\App\Models\CustomField::ENTITIES as $entity => $label)
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ $label }}</h2>@can('settings.edit')<a href="{{ route('custom-fields.create', ['entity' => $entity]) }}" class="text-sm link">+ Añadir</a>@endcan</div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($fields[$entity] ?? [] as $field)
                        <li class="flex items-center justify-between px-5 py-3">
                            <div>
                                <p class="font-medium">{{ $field->label }} @if ($field->is_required)<span class="text-rose-500">*</span>@endif</p>
                                <p class="text-xs text-slate-400">{{ \App\Models\CustomField::TYPES[$field->type] }} · <code>{{ $field->name }}</code>{{ $field->show_on_document ? ' · visible en documentos' : '' }}{{ $field->is_active ? '' : ' · inactivo' }}</p>
                            </div>
                            @can('settings.edit')<span class="flex gap-1"><a href="{{ route('custom-fields.edit', $field) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a><x-delete-button :action="route('custom-fields.destroy', $field)" :small="true"/></span>@endcan
                        </li>
                    @empty
                        <li class="px-5 py-4 text-slate-400">Sin campos personalizados.</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>
</x-app-layout>
