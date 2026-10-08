@php
    $messages = [
        'success' => ['bg-emerald-50 text-emerald-800 ring-emerald-200', 'check'],
        'warning' => ['bg-amber-50 text-amber-800 ring-amber-200', 'warning'],
        'error' => ['bg-rose-50 text-rose-800 ring-rose-200', 'warning'],
        'status' => ['bg-sky-50 text-sky-800 ring-sky-200', 'info'],
    ];
@endphp
<div class="space-y-3 mb-5 empty:mb-0">
    @foreach ($messages as $key => [$classes, $icon])
        @if (session($key) && ! in_array(session($key), ['profile-updated', 'password-updated', 'verification-link-sent']))
            <div x-data="{ show: true }" x-show="show" x-transition class="flex items-start gap-3 rounded-lg px-4 py-3 text-sm ring-1 {{ $classes }}">
                <x-icon :name="$icon" class="mt-0.5 h-5 w-5"/>
                <div class="flex-1">{{ session($key) }}</div>
                <button type="button" @click="show = false" class="opacity-60 hover:opacity-100"><x-icon name="x" class="h-4 w-4"/></button>
            </div>
        @endif
    @endforeach
    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">
            <x-icon name="warning" class="mt-0.5 h-5 w-5"/>
            <div>
                <p class="font-medium">Revise los datos del formulario:</p>
                <ul class="mt-1 list-disc pl-5 space-y-0.5">
                    @foreach (array_slice($errors->all(), 0, 6) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
</div>
