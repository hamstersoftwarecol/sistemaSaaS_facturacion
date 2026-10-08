<x-app-layout :title="$category->exists ? 'Editar categoría' : 'Nueva categoría'">
    <x-page-header :title="$category->exists ? 'Editar categoría' : 'Nueva categoría'" :back="route('categories.index', ['type' => $category->type])"/>
    <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" class="card card-body grid max-w-2xl grid-cols-1 gap-5 sm:grid-cols-3">
        @csrf
        @if ($category->exists) @method('PUT') @endif
        <x-form.select name="type" label="Tipo" :options="\App\Models\Category::TYPES" :value="$category->type"/>
        <x-form.input name="name" label="Nombre" :value="$category->name" required/>
        <x-form.input name="color" type="color" label="Color" :value="$category->color ?? '#6366f1'" class="[&_input]:h-10"/>
        <div class="sm:col-span-3"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar</button></div>
    </form>
</x-app-layout>
