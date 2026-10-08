<x-app-layout :title="$formula->exists ? 'Editar fórmula' : 'Nueva fórmula'">
    <x-page-header :title="$formula->exists ? 'Editar '.$formula->name : 'Nueva fórmula empresarial'" :back="route('formulas.index')"/>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ $formula->exists ? route('formulas.update', $formula) : route('formulas.store') }}" class="card card-body grid grid-cols-1 gap-5 sm:grid-cols-2 xl:col-span-2">
            @csrf
            @if ($formula->exists) @method('PUT') @endif
            <x-form.input name="name" label="Nombre (se muestra en la factura)" :value="$formula->name" required placeholder="Retención en la fuente"/>
            <x-form.input name="code" label="Código (variable)" :value="$formula->code" required placeholder="retencion" hint="Letras, números y guion bajo."/>
            <x-form.input name="expression" label="Expresión" :value="$formula->expression" required class="sm:col-span-2 [&_input]:font-mono" placeholder="subtotal * 0.025"/>
            <x-form.select name="applies_to" label="Aplica a" :options="\App\Models\Formula::APPLIES_TO" :value="$formula->applies_to"/>
            <x-form.select name="action" label="Efecto en el total" :options="\App\Models\Formula::ACTIONS" :value="$formula->action"/>
            <x-form.input name="sort_order" type="number" label="Orden de cálculo" :value="$formula->sort_order ?? 0"/>
            <x-form.input name="description" label="Descripción" :value="$formula->description"/>
            <x-form.checkbox name="is_active" label="Fórmula activa" :checked="$formula->is_active"/>
            <div class="sm:col-span-2"><button class="btn-primary"><x-icon name="check" class="h-4 w-4"/> Guardar fórmula</button></div>
        </form>
        @include('formulas._help')
    </div>
</x-app-layout>
