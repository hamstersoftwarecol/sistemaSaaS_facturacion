<x-app-layout title="Perfil de la empresa">
    <x-page-header title="Perfil de la empresa" subtitle="Datos fiscales y de contacto que aparecen en sus documentos"/>
    <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data" class="grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-3">
        @csrf @method('PUT')
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Identificación</h2></div>
                <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.input name="name" label="Nombre comercial" :value="$company->name" required/>
                    <x-form.input name="legal_name" label="Razón social" :value="$company->legal_name"/>
                    <x-form.input name="tax_id" label="NIT / RUC / RFC" :value="$company->tax_id"/>
                    <x-form.input name="email" type="email" label="Correo" :value="$company->email"/>
                    <x-form.input name="phone" label="Teléfono" :value="$company->phone"/>
                    <x-form.input name="website" label="Sitio web" :value="$company->website"/>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Dirección</h2></div>
                <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.input name="address" label="Dirección" :value="$company->address" class="sm:col-span-2"/>
                    <x-form.input name="city" label="Ciudad" :value="$company->city"/>
                    <x-form.input name="state" label="Departamento / estado" :value="$company->state"/>
                    <x-form.input name="postal_code" label="Código postal" :value="$company->postal_code"/>
                    <x-form.input name="country" label="País" :value="$company->country"/>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Moneda</h2></div>
                <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-form.input name="currency" label="Código ISO" :value="$company->currency" maxlength="3" required/>
                    <x-form.input name="currency_symbol" label="Símbolo" :value="$company->currency_symbol" required/>
                </div>
            </div>
        </div>
        <div class="space-y-6">
            <div class="card card-body" x-data="{ preview: @js($company->logoUrl()) }">
                <h2 class="card-title mb-3">Logotipo</h2>
                <div class="flex h-36 items-center justify-center rounded-lg border-2 border-dashed border-slate-200 bg-slate-50">
                    <template x-if="preview"><img :src="preview" class="max-h-32 max-w-full object-contain"></template>
                    <template x-if="!preview"><span class="text-sm text-slate-400">Sin logotipo</span></template>
                </div>
                <input type="file" name="logo" accept="image/*" class="mt-3 block w-full text-sm" @change="preview = URL.createObjectURL($event.target.files[0])">
                <p class="form-hint">PNG, JPG o WEBP, máx. 1 MB.</p>
                @error('logo')<p class="form-error">{{ $message }}</p>@enderror
                @if ($company->logo_path)<x-form.checkbox name="remove_logo" label="Quitar logotipo actual" class="mt-3"/>@endif
            </div>
            @can('settings.edit')<button class="btn-primary w-full"><x-icon name="check" class="h-4 w-4"/> Guardar cambios</button>@endcan
            <a href="{{ route('settings.invoice') }}" class="btn-secondary w-full"><x-icon name="swatch" class="h-4 w-4"/> Diseño de facturas</a>
        </div>
    </form>
</x-app-layout>
