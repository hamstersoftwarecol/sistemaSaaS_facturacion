<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ $currentCompany?->name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>window.APP_CURRENCY = @js($currentCompany?->currency_symbol ?? '$');</script>
    @stack('head')
</head>
<body class="font-sans" x-data="{ sidebar: false }">
    <div class="min-h-screen lg:pl-64">
        {{-- Barra lateral --}}
        <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/60 lg:hidden" @click="sidebar = false"></div>
        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 transition-transform lg:translate-x-0"
               :class="sidebar && '!translate-x-0'">
            <div class="flex h-16 shrink-0 items-center gap-3 px-5 border-b border-white/5">
                @if ($currentCompany?->logoUrl())
                    <img src="{{ $currentCompany->logoUrl() }}" alt="" class="h-9 w-9 rounded-lg bg-white object-contain p-0.5">
                @else
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-violet-500 text-sm font-bold text-white">
                        {{ mb_strtoupper(mb_substr($currentCompany?->name ?? 'F', 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-white">{{ $currentCompany?->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $currentCompany?->subscription?->plan?->name ?? 'Sin plan' }}</p>
                </div>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 pb-6">
                @include('layouts.partials.sidebar')
            </nav>
        </aside>

        {{-- Barra superior --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button type="button" class="btn-ghost -ml-2 lg:hidden" @click="sidebar = true"><x-icon name="menu" class="h-6 w-6"/></button>

            @if (auth()->user()->branch_id)
                <span class="hidden items-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-sm text-slate-600 sm:inline-flex">
                    <x-icon name="map-pin" class="h-4 w-4"/> {{ $currentBranch?->name }}
                </span>
            @elseif ($availableBranches->count() > 1)
                <form method="POST" action="{{ route('branch.switch') }}" class="flex items-center gap-2">
                    @csrf
                    <x-icon name="map-pin" class="hidden h-4 w-4 text-slate-400 sm:block"/>
                    <select name="branch_id" onchange="this.form.submit()" class="form-select w-44 py-1.5 sm:w-56" aria-label="Sucursal activa">
                        <option value="">Todas las sucursales</option>
                        @foreach ($availableBranches as $branch)
                            <option value="{{ $branch->id }}" @selected($currentBranch?->id === $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            <div class="ml-auto flex items-center gap-2">
                @can('invoices.create')
                    <a href="{{ route('invoices.quick') }}" class="btn-secondary hidden sm:inline-flex" title="Facturación rápida con teclado">
                        <x-icon name="bolt" class="h-4 w-4 text-amber-500"/> Venta rápida
                    </a>
                    <a href="{{ route('invoices.create') }}" class="btn-primary hidden sm:inline-flex"><x-icon name="plus" class="h-4 w-4"/> Factura</a>
                @endcan

                <x-dropdown align="right" width="w-56">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-slate-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">{{ auth()->user()->initials() }}</span>
                            <span class="hidden text-left md:block">
                                <span class="block text-sm font-medium leading-4 text-slate-800">{{ auth()->user()->name }}</span>
                                <span class="block text-xs text-slate-500">{{ auth()->user()->roleName() }}</span>
                            </span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-slate-400"/>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Mi perfil</x-dropdown-link>
                        <x-dropdown-link :href="route('subscription.index')">Suscripción</x-dropdown-link>
                        @can('settings.view')
                            <x-dropdown-link :href="route('settings.company')">Perfil de la empresa</x-dropdown-link>
                        @endcan
                        @if (auth()->user()->is_super_admin)
                            <x-dropdown-link :href="route('admin.dashboard')">Administración SaaS</x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            @php $sub = $currentCompany?->subscription; @endphp
            @if ($sub && $sub->status === 'trialing' && $sub->isValid() && $sub->daysLeft() !== null && $sub->daysLeft() <= 7)
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-3 text-sm text-white">
                    <span><strong>Periodo de prueba:</strong> le quedan {{ $sub->daysLeft() }} días del plan {{ $sub->plan->name }}.</span>
                    <a href="{{ route('subscription.index') }}" class="rounded-md bg-white/15 px-3 py-1 font-medium hover:bg-white/25">Activar suscripción</a>
                </div>
            @endif
            <x-flash/>
            {{ $slot }}
        </main>
    </div>
    @stack('scripts')
</body>
</html>
