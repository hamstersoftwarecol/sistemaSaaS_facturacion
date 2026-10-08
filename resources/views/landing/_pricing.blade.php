<div x-data="{ cycle: 'monthly' }">
    <div class="flex justify-center">
        <div class="inline-flex rounded-full bg-slate-100 p-1">
            <button type="button" @click="cycle = 'monthly'" class="rounded-full px-5 py-2 text-sm font-semibold transition" :class="cycle === 'monthly' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'">Mensual</button>
            <button type="button" @click="cycle = 'yearly'" class="rounded-full px-5 py-2 text-sm font-semibold transition" :class="cycle === 'yearly' ? 'bg-white text-slate-900 shadow' : 'text-slate-500'">Anual <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">2 meses gratis</span></button>
        </div>
    </div>
    <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($plans as $plan)
            <div class="relative flex flex-col rounded-2xl border p-7 {{ $plan->is_featured ? 'border-indigo-500 bg-gradient-to-b from-indigo-50 to-white shadow-xl shadow-indigo-500/10' : 'border-slate-200 bg-white' }}">
                @if ($plan->is_featured)<span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">Más popular</span>@endif
                <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                <p class="mt-1 min-h-[40px] text-sm text-slate-500">{{ $plan->description }}</p>
                <p class="mt-5 flex items-baseline gap-1">
                    <span class="text-4xl font-extrabold tracking-tight text-slate-900" x-text="cycle === 'monthly' ? '${{ number_format($plan->price_monthly, 0) }}' : '${{ number_format($plan->price_yearly, 0) }}'"></span>
                    <span class="text-sm text-slate-500" x-text="cycle === 'monthly' ? '/mes' : '/año'"></span>
                </p>
                <p class="mt-1 text-xs text-slate-400">{{ $plan->isFree() ? 'Gratis para siempre' : $plan->trial_days.' días de prueba gratis' }}</p>
                <a href="{{ route('register', ['plan' => $plan->slug]) }}" class="{{ $plan->is_featured ? 'btn-primary' : 'btn-secondary' }} mt-6 w-full py-2.5">Comenzar</a>
                <ul class="mt-7 space-y-3 text-sm text-slate-600">
                    @foreach ($plan->limitLines() as $line)
                        <li class="flex gap-2"><x-icon name="check" class="h-5 w-5 text-indigo-500"/>{{ $line }}</li>
                    @endforeach
                    @foreach ($plan->features ?? [] as $feature)
                        <li class="flex gap-2"><x-icon name="check" class="h-5 w-5 text-indigo-500"/>{{ $feature }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
