@props(['title' => 'No hay registros todavía', 'description' => null, 'icon' => 'document'])
<div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-500">
        <x-icon :name="$icon" class="h-6 w-6"/>
    </div>
    <h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
