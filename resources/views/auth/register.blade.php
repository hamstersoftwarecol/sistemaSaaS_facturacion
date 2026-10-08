<x-guest-layout>
    <h1 class="text-2xl font-bold text-slate-900">Cree la cuenta de su empresa</h1>
    <p class="mt-1 text-sm text-slate-500">Configure su espacio de facturación en menos de un minuto. Sin tarjeta de crédito.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="company_name" label="Nombre de la empresa" required autofocus/>
        <x-form.input name="name" label="Su nombre completo" required autocomplete="name"/>
        <x-form.input name="email" type="email" label="Correo electrónico" required autocomplete="username"/>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-form.select name="plan" label="Plan" :value="request('plan', $plans->firstWhere('is_featured', true)?->slug ?? $plans->first()?->slug)"
                :options="$plans->mapWithKeys(fn ($p) => [$p->slug => $p->name.' — '.($p->isFree() ? 'Gratis' : '$'.number_format($p->price_monthly, 0).'/mes')])->all()" required/>
            <x-form.select name="currency" label="Moneda" :value="old('currency', 'USD')" required
                :options="['USD' => 'USD — Dólar', 'COP' => 'COP — Peso colombiano', 'MXN' => 'MXN — Peso mexicano', 'EUR' => 'EUR — Euro', 'PEN' => 'PEN — Sol', 'CLP' => 'CLP — Peso chileno', 'ARS' => 'ARS — Peso argentino']"/>
        </div>

        <x-form.input name="password" type="password" label="Contraseña" required autocomplete="new-password"/>
        <x-form.input name="password_confirmation" type="password" label="Confirmar contraseña" required autocomplete="new-password"/>

        <button type="submit" class="btn-primary w-full py-2.5">Crear cuenta y comenzar prueba</button>
        <p class="text-center text-xs text-slate-500">Los planes de pago incluyen un periodo de prueba gratuito.</p>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        ¿Ya tiene cuenta? <a href="{{ route('login') }}" class="link">Iniciar sesión</a>
    </p>
</x-guest-layout>
