<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name').' · Software de facturación SaaS' }}</title>
    <meta name="description" content="Software de facturación en la nube multi-sucursal: facturas con QR, inventario, pagos, bancos e informes.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans" x-data="{ menu: false }">
    <header class="sticky top-0 z-40 border-b border-slate-100 bg-white/80 backdrop-blur">
        <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5"><x-application-logo class="h-9 w-9"/><span class="text-lg font-bold text-slate-900">{{ config('app.name') }}</span></a>
            <div class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
                <a href="{{ route('home') }}#funciones" class="hover:text-slate-900">Funciones</a>
                <a href="{{ route('home') }}#disenos" class="hover:text-slate-900">Diseños de factura</a>
                <a href="{{ route('pricing') }}" class="hover:text-slate-900">Precios</a>
                <a href="{{ route('home') }}#faq" class="hover:text-slate-900">Preguntas</a>
            </div>
            <div class="flex items-center gap-2">
                @auth
                    <a href="{{ auth()->user()->company_id ? route('dashboard') : route('admin.dashboard') }}" class="btn-primary">Ir al panel</a>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost hidden sm:inline-flex">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="btn-primary">Prueba gratis</a>
                @endauth
            </div>
        </nav>
    </header>
    {{ $slot }}
    <footer class="border-t border-slate-100 bg-slate-50">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 md:grid-cols-4">
            <div class="col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5"><x-application-logo class="h-8 w-8"/><span class="font-bold">{{ config('app.name') }}</span></a>
                <p class="mt-3 max-w-sm text-sm text-slate-500">Software de facturación y cobros en la nube para empresas con una o varias sucursales.</p>
            </div>
            <div><p class="text-sm font-semibold">Producto</p><ul class="mt-3 space-y-2 text-sm text-slate-500"><li><a href="{{ route('home') }}#funciones">Funciones</a></li><li><a href="{{ route('pricing') }}">Precios</a></li><li><a href="{{ route('register') }}">Crear cuenta</a></li></ul></div>
            <div><p class="text-sm font-semibold">Cuenta</p><ul class="mt-3 space-y-2 text-sm text-slate-500"><li><a href="{{ route('login') }}">Iniciar sesión</a></li><li><a href="{{ route('password.request') }}">Recuperar contraseña</a></li></ul></div>
        </div>
        <p class="border-t border-slate-200 py-6 text-center text-xs text-slate-400">© {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
