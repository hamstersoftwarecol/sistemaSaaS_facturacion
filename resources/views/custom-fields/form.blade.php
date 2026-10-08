<x-app-layout :title="$field->exists ? 'Editar campo' : 'Nuevo campo'">
    <x-page-header :title="$field->exists ? 'Editar campo '.$field->label : 'Nuevo campo personalizado'" :back="route('custom-fields.index')"/>
    <form method="POST" action="{{ $field->exists ? route('custom-fields.update', $field) : route('custom-fields.store') }}" class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2" x-data="{ type: @js(old('type', $field->type)) }">
        @csrf
        @if ($field->exists) @method('PUT') @endif
        <x-form.select name="entity" label="Aplicar a" :options="\App\Models\CustomField::ENTITIES" :value="$field->entity" required/>
        <x-form.input name="label" label="Etiqueta" :value="$field->label" required placeholder="Ej.: Vendedor asignado, Garantía, Placa…"/>
        <div><label class="form-label">Tipo de dato</label><select name="type" x-model="type" class="form-select">@foreach (\App\Models\CustomField::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <x-form.input name="sort_order" type="number" label="Orden" :value="$field->sort_order ?? 0"/>
        <div class="sm:col-span-2" x-show="type === 'select'"><x-form.textarea name="options" label="Opciones (una por línea)" :value="implode(PHP_EOL, $field->options ?? [])" rows="4"/></div>
        <x-form.checkbox name="is_required" label="Obligatorio" :checked="$field->is_required"/>
        <x-form.checkbox name="show_on_document" label="Mostrar en facturas impresas" :checked="$field->show_on_document"/>
        <x-form.checkbox name="is_active" label="Activo" :checked="$field->is_active"/>
        <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar campo</button></div>
    </form>
</x-app-layout>
