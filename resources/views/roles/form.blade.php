@php $selected = old('permissions', $role->permissions ?? []); @endphp
<x-app-layout :title="$role->exists ? 'Editar rol' : 'Nuevo rol'">
    <x-page-header :title="$role->exists ? 'Permisos de '.$role->name : 'Nuevo rol'" :back="route('roles.index')"/>
    <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}" class="space-y-6" x-data="{ selected: @js($selected),
            toggleModule(keys, on) { keys.forEach(k => { const i = this.selected.indexOf(k); if (on && i < 0) this.selected.push(k); if (!on && i >= 0) this.selected.splice(i, 1) }) },
            all(keys) { return keys.every(k => this.selected.includes(k)) } }">
        @csrf
        @if ($role->exists) @method('PUT') @endif
        <div class="card card-body grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Nombre del rol" :value="$role->name" required/>
            <x-form.input name="description" label="Descripción" :value="$role->description"/>
        </div>
        <div class="flex gap-2">
            <button type="button" class="btn-secondary btn-sm" @click="selected = @js(\App\Support\Permissions::all())">Seleccionar todo</button>
            <button type="button" class="btn-secondary btn-sm" @click="selected = []">Quitar todo</button>
        </div>
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            @foreach ($groups as $group => $modules)
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ $group }}</h2></div>
                    <table class="table">
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($modules as $module => $definition)
                                @php $keys = collect(array_keys($definition['actions']))->map(fn ($a) => "$module.$a")->all(); @endphp
                                <tr>
                                    <td class="w-1/3">
                                        <label class="inline-flex items-center gap-2 font-medium">
                                            <input type="checkbox" class="form-checkbox" :checked="all(@js($keys))" @change="toggleModule(@js($keys), $event.target.checked)">
                                            {{ $definition['label'] }}
                                        </label>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                                            @foreach ($definition['actions'] as $action => $label)
                                                <label class="inline-flex items-center gap-1.5 text-sm text-slate-600">
                                                    <input type="checkbox" name="permissions[]" value="{{ $module }}.{{ $action }}" x-model="selected" class="form-checkbox">{{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
        <button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar rol</button>
    </form>
</x-app-layout>
