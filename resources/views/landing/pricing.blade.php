<x-public-layout :title="'Precios · '.config('app.name')">
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Planes y precios</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Un plan para cada etapa de su negocio</h1>
            <p class="mt-4 text-lg text-slate-500">Comience gratis y crezca sin cambiar de sistema. Todos los planes incluyen los 5 diseños de factura con QR.</p>
        </div>
        <div class="mt-14">@include('landing._pricing')</div>
        <div class="mx-auto mt-16 max-w-3xl rounded-2xl bg-slate-50 p-8 text-center">
            <h2 class="text-xl font-bold">¿Necesita un plan a medida?</h2>
            <p class="mt-2 text-slate-500">Para cadenas con muchas sucursales o necesidades especiales de integración, contáctenos.</p>
        </div>
    </section>
</x-public-layout>
