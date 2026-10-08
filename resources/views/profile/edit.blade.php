<x-dynamic-component :component="auth()->user()->company_id ? 'app-layout' : 'admin-layout'" title="Mi perfil">
    <x-page-header title="Mi perfil" subtitle="Datos de acceso y seguridad de su cuenta"/>

    <div class="grid max-w-4xl gap-6">
        <div class="card card-body">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="card card-body">
            @include('profile.partials.update-password-form')
        </div>
        @unless (auth()->user()->is_owner || auth()->user()->is_super_admin)
            <div class="card card-body">
                @include('profile.partials.delete-user-form')
            </div>
        @endunless
    </div>
</x-dynamic-component>
