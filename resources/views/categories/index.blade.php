<x-app-layout title="Categorías">
    <x-page-header title="Categorías" subtitle="Clasifique productos, gastos e ingresos">
        @can('settings.edit')<a href="{{ route('categories.create', ['type' => $type]) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nueva categoría</a>@endcan
    </x-page-header>
    <div class="mb-4 inline-flex rounded-lg bg-white p-1 ring-1 ring-slate-200">
        @foreach (\App\Models\Category::TYPES as $key => $label)
            <a href="{{ route('categories.index', ['type' => $key]) }}" class="rounded-md px-4 py-1.5 text-sm font-medium {{ $type === $key ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="card max-w-3xl">
        <ul class="divide-y divide-slate-100">
            @forelse ($categories as $category)
                <li class="flex items-center justify-between px-5 py-3">
                    <span class="flex items-center gap-3"><span class="h-3 w-3 rounded-full" style="background: {{ $category->color ?? '#94a3b8' }}"></span>{{ $category->name }}</span>
                    @can('settings.edit')<span class="flex gap-1"><a href="{{ route('categories.edit', $category) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="h-4 w-4"/></a><x-delete-button :action="route('categories.destroy', $category)" :small="true"/></span>@endcan
                </li>
            @empty
                <li class="px-5 py-8 text-center text-slate-500">Sin categorías.</li>
            @endforelse
        </ul>
    </div>
</x-app-layout>
