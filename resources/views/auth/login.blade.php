<x-guest-layout>
    <h1 class="text-2xl font-bold text-slate-900">Bienvenido de nuevo</h1>
    <p class="mt-1 text-sm text-slate-500">Inicie sesión para acceder al panel de su empresa.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Correo electrónico" :value="old('email')" required autofocus autocomplete="username"/>
        <x-form.input name="password" type="password" label="Contraseña" required autocomplete="current-password"/>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input id="remember_me" type="checkbox" class="form-checkbox" name="remember">
                <span class="text-sm text-slate-600">Recordarme</span>
            </label>
            @if (Route::has('password.request'))
                <a class="link text-sm" href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full py-2.5">Iniciar sesión</button>
    </form>

    <p class="mt-8 text-center text-sm text-slate-500">
        ¿Aún no tiene cuenta? <a href="{{ route('register') }}" class="link">Pruebe gratis</a>
    </p>

    @if (app()->environment('local'))
        <div class="mt-8 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-xs text-slate-600">
            <p class="mb-2 font-semibold text-slate-700">Cuentas de demostración (contraseña: <code>password</code>)</p>
            <ul class="space-y-1">
                <li><code>admin@facturapro.test</code> — Super administrador SaaS</li>
                <li><code>demo@facturapro.test</code> — Propietario de empresa demo</li>
                <li><code>vendedor@facturapro.test</code> — Rol Vendedor</li>
                <li><code>contador@facturapro.test</code> — Rol Contador</li>
            </ul>
        </div>
    @endif
</x-guest-layout>
