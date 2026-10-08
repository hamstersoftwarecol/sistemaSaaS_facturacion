<x-guest-layout>
    <h1 class="text-2xl font-bold text-slate-900">Nueva contraseña</h1>
    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-form.input name="email" type="email" label="Correo electrónico" :value="old('email', $request->email)" required autofocus/>
        <x-form.input name="password" type="password" label="Nueva contraseña" required autocomplete="new-password"/>
        <x-form.input name="password_confirmation" type="password" label="Confirmar contraseña" required autocomplete="new-password"/>
        <button type="submit" class="btn-primary w-full py-2.5">Restablecer contraseña</button>
    </form>
</x-guest-layout>
