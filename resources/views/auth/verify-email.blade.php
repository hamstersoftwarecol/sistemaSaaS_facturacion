<x-guest-layout>
    <h1 class="text-2xl font-bold text-slate-900">Verifique su correo</h1>
    <p class="mt-2 text-sm text-slate-600">Gracias por registrarse. Antes de comenzar, verifique su dirección de correo haciendo clic en el enlace que le enviamos.</p>
    @if (session('status') == 'verification-link-sent')
        <p class="mt-4 text-sm font-medium text-emerald-600">Se ha enviado un nuevo enlace de verificación a su correo.</p>
    @endif
    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary">Reenviar correo de verificación</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="link text-sm">Cerrar sesión</button>
        </form>
    </div>
</x-guest-layout>
