<x-admin-layout title="Métodos de pago">
    <x-page-header title="Métodos de pago" subtitle="Formas en que las empresas pueden pagar su suscripción">
        <a href="{{ route('admin.payment-methods.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4"/> Nuevo método</a>
    </x-page-header>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($methods as $method)
            <div class="card p-5">
                <div class="flex items-start justify-between"><div><p class="font-semibold">{{ $method->name }}</p><p class="text-xs text-slate-500">{{ $method->typeLabel() }}</p></div><x-badge :color="$method->is_active ? 'green' : 'gray'">{{ $method->is_active ? 'Activo' : 'Inactivo' }}</x-badge></div>
                <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $method->instructions }}</p>
                <div class="mt-4 flex gap-2"><a href="{{ route('admin.payment-methods.edit', $method) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-4 w-4"/> Editar</a><x-delete-button :action="route('admin.payment-methods.destroy', $method)" :small="true"/></div>
            </div>
        @endforeach
    </div>
</x-admin-layout>
