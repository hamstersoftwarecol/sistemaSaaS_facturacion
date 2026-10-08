<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} · Acceso</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">
    <div class="grid min-h-screen lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-700 lg:flex lg:flex-col lg:justify-between p-12 text-white">
            <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-20 h-96 w-96 rounded-full bg-fuchsia-400/20 blur-3xl"></div>
            <a href="{{ route('home') }}" class="relative flex items-center gap-3">
                <x-application-logo class="h-10 w-10"/>
                <span class="text-lg font-semibold">{{ config('app.name') }}</span>
            </a>
            <div class="relative max-w-md">
                <h2 class="text-3xl font-bold leading-tight">Facturación, cobros e inventario para todas sus sucursales.</h2>
                <p class="mt-4 text-indigo-100">Emita facturas con verificación QR, controle el stock por sucursal, concilie sus bancos y tome decisiones con informes en tiempo real.</p>
                <ul class="mt-8 space-y-3 text-sm text-indigo-50">
                    @foreach (['5 diseños de factura profesionales con código QR', 'Roles y permisos por usuario', 'Efectivo, bancos, cheques y préstamos', 'Informes de ventas, impuestos y resultados'] as $item)
                        <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15"><x-icon name="check" class="h-4 w-4"/></span>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
            <p class="relative text-xs text-indigo-200">© {{ date('Y') }} {{ config('app.name') }}. Software de facturación en la nube.</p>
        </div>
        <div class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-application-logo class="h-10 w-10"/>
                    <span class="text-lg font-semibold text-slate-900">{{ config('app.name') }}</span>
                </a>
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
