<x-app-layout title="Contratar plan">
    <x-page-header :title="'Plan '.$plan->name" subtitle="Complete el pago para activar o renovar su suscripción" :back="route('subscription.index')"/>
    <form method="POST" action="{{ route('subscription.pay', $plan) }}" class="grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-3" x-data="{ cycle: @js($cycle), method: @js((string) old('payment_method_id', $methods->first()?->id)) }">
        @csrf
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <h2 class="card-title mb-3">Ciclo de facturación</h2>
                <div class="grid grid-cols-2 gap-3">
                    @foreach (['monthly' => ['Mensual', $plan->price_monthly, '/mes'], 'yearly' => ['Anual', $plan->price_yearly, '/año']] as $key => [$label, $price, $suffix])
                        <label class="cursor-pointer rounded-xl border-2 p-4" :class="cycle === '{{ $key }}' ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200'">
                            <input type="radio" name="billing_cycle" value="{{ $key }}" x-model="cycle" class="sr-only">
                            <p class="font-semibold">{{ $label }}</p>
                            <p class="text-2xl font-bold">${{ number_format($price, 2) }}<span class="text-sm font-normal text-slate-500">{{ $suffix }}</span></p>
                        </label>
                    @endforeach
                </div>
            </div>
            @unless ($plan->isFree())
                <div class="card card-body">
                    <h2 class="card-title mb-3">Método de pago</h2>
                    <div class="space-y-3">
                        @foreach ($methods as $method)
                            <label class="block cursor-pointer rounded-xl border-2 p-4" :class="method === '{{ $method->id }}' ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-200'">
                                <input type="radio" name="payment_method_id" value="{{ $method->id }}" x-model="method" class="sr-only">
                                <div class="flex items-center justify-between"><p class="font-semibold">{{ $method->name }}</p><x-badge>{{ $method->typeLabel() }}</x-badge></div>
                                <p x-show="method === '{{ $method->id }}'" class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $method->instructions }}</p>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_method_id')<p class="form-error">{{ $message }}</p>@enderror
                    <div class="mt-5 grid grid-cols-1 gap-4">
                        <x-form.input name="reference" label="Referencia o número de transacción" required placeholder="Ej.: TRX-123456"/>
                        <x-form.textarea name="notes" label="Comentarios (opcional)" rows="2"/>
                    </div>
                </div>
            @endunless
        </div>
        <div class="card card-body h-fit space-y-3">
            <h2 class="card-title">Resumen</h2>
            <div class="flex justify-between text-sm"><span class="text-slate-500">Empresa</span><span>{{ $company->name }}</span></div>
            <div class="flex justify-between text-sm"><span class="text-slate-500">Plan</span><span>{{ $plan->name }}</span></div>
            <div class="flex justify-between border-t border-slate-100 pt-3 text-lg font-bold"><span>Total</span>
                <span x-text="cycle === 'monthly' ? '${{ number_format($plan->price_monthly, 2) }}' : '${{ number_format($plan->price_yearly, 2) }}'"></span></div>
            <button class="btn-primary w-full py-2.5">{{ $plan->isFree() ? 'Activar plan gratuito' : 'Enviar pago para verificación' }}</button>
            @unless ($plan->isFree())<p class="text-xs text-slate-500">El administrador de la plataforma verificará el pago y activará su plan.</p>@endunless
        </div>
    </form>
</x-app-layout>
