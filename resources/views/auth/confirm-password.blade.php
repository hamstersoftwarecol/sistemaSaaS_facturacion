<x-guest-layout>
    <h1 class="text-2xl font-bold text-slate-900">Confirme su contraseña</h1>
    <p class="mt-1 text-sm text-slate-500">Esta es un área segura. Confirme su contraseña para continuar.</p>
    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="password" type="password" label="Contraseña" required autocomplete="current-password"/>
        <button type="submit" class="btn-primary w-full py-2.5">Confirmar</button>
    </form>
</x-guest-layout>
