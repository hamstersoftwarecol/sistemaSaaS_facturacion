<x-guest-layout>
    <h1 class="text-2xl font-bold text-slate-900">¿Olvidó su contraseña?</h1>
    <p class="mt-1 text-sm text-slate-500">Indique su correo y le enviaremos un enlace para crear una nueva.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Correo electrónico" required autofocus/>
        <button type="submit" class="btn-primary w-full py-2.5">Enviar enlace de recuperación</button>
    </form>
    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" class="link">Volver al inicio de sesión</a></p>
</x-guest-layout>
