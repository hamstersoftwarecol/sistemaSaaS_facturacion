<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}Administración · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>window.APP_CURRENCY = '$';</script>
</head>
<body class="font-sans" x-data="{ sidebar: false }">
    @php
        $nav = [
            ['Resumen', 'admin.dashboard', 'chart', 'admin.dashboard'],
            ['Empresas', 'admin.companies.index', 'building', 'admin.companies.*'],
            ['Planes', 'admin.plans.index', 'sparkles', 'admin.plans.*'],
            ['Suscripciones', 'admin.subscriptions.index', 'refresh', 'admin.subscriptions.*'],
            ['Pagos de suscripción', 'admin.payments.index', 'banknotes', 'admin.payments.*'],
            ['Métodos de pago', 'admin.payment-methods.index', 'card', 'admin.payment-methods.*'],
        ];
        $pending = \App\Models\SubscriptionPayment::where('status', 'pending')->count();
    @endphp
    <div class="min-h-screen lg:pl-64">
        <div x-show="sidebar" x-cloak class="fixed inset-0 z-30 bg-slate-900/60 lg:hidden" @click="sidebar = false"></div>
        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gradient-to-b from-slate-950 to-indigo-950 transition-transform lg:translate-x-0"
               :class="sidebar && '!translate-x-0'">
            <div class="flex h-16 items-center gap-3 border-b border-white/5 px-5">
                <x-application-logo class="h-9 w-9"/>
                <div>
                    <p class="text-sm font-semibold text-white">{{ config('app.name') }}</p>
                    <p class="text-xs text-indigo-300">Panel de plataforma</p>
                </div>
            </div>
            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-5">
                @foreach ($nav as [$label, $route, $icon, $pattern])
                    <a href="{{ route($route) }}" class="nav-item {{ request()->routeIs($pattern) ? 'nav-item-active' : '' }}">
                        <x-icon :name="$icon" class="h-[18px] w-[18px] opacity-80"/>
                        <span class="flex-1">{{ $label }}</span>
                        @if ($route === 'admin.payments.index' && $pending)
                            <span class="rounded-full bg-amber-400 px-2 text-xs font-semibold text-amber-950">{{ $pending }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-white/5 p-4 text-xs text-slate-400">
                Conectado como <span class="text-slate-200">{{ auth()->user()->email }}</span>
            </div>
        </aside>

        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
            <button type="button" class="btn-ghost -ml-2 lg:hidden" @click="sidebar = true"><x-icon name="menu" class="h-6 w-6"/></button>
            <span class="badge bg-indigo-50 text-indigo-700 ring-indigo-600/20">Super administrador</span>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('home') }}" class="btn-ghost" target="_blank">Ver sitio público</a>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-slate-100">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">{{ auth()->user()->initials() }}</span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-slate-400"/>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Mi perfil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <x-flash/>
            {{ $slot }}
        </main>
    </div>
    @stack('scripts')
</body>
</html>
