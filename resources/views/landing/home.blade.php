@php
    $modules = [
        ['Panel de administración SaaS', 'Gestione empresas, planes, suscripciones, pagos y métodos de cobro de toda la plataforma.', 'building', 'from-indigo-500 to-violet-500'],
        ['Multi-sucursal', 'Sucursales, usuarios, roles y permisos granulares por módulo. Cada usuario ve solo lo que debe.', 'map-pin', 'from-sky-500 to-cyan-500'],
        ['Ciclo de ventas completo', 'Clientes, presupuestos, facturas, facturas recurrentes, albaranes y notas de crédito.', 'document', 'from-emerald-500 to-teal-500'],
        ['Cobros y cartera', 'Pagos recibidos, libro mayor de clientes, antigüedad de saldos y recibos de caja.', 'inbox', 'from-amber-500 to-orange-500'],
        ['Compras y proveedores', 'Proveedores, órdenes de compra, recepción de mercancía, notas de débito y pagos.', 'shopping-cart', 'from-rose-500 to-pink-500'],
        ['Tesorería', 'Efectivo y bancos, gastos, otros ingresos, cheques, conciliación bancaria, préstamos y cuotas.', 'bank', 'from-blue-600 to-indigo-600'],
        ['Inventario', 'Productos, listas de precios, stock por sucursal, ajustes, transferencias y kardex.', 'cube', 'from-fuchsia-500 to-purple-600'],
        ['Código de barras y venta rápida', 'Etiquetas Code128 imprimibles y facturación con lector o solo teclado (F2, F9…).', 'barcode', 'from-lime-500 to-green-600'],
        ['Informes y configuración', 'Ventas, impuestos, P&G, flujo de caja; tipos impositivos, campos personalizados y fórmulas.', 'chart', 'from-slate-600 to-slate-800'],
    ];
    $styleColors = ['modern' => '#4f46e5', 'classic' => '#111827', 'corporate' => '#0f2a4a', 'elegant' => '#a47e3b', 'bold' => '#e11d48'];
@endphp
<x-public-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-100 via-white to-white"></div>
        <div class="absolute -right-40 top-20 -z-10 h-[30rem] w-[30rem] rounded-full bg-violet-200/40 blur-3xl"></div>
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-2 lg:pt-24">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200"><x-icon name="sparkles" class="h-4 w-4"/> Software de facturación tipo ERP en la nube</span>
                <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-6xl">Facture, cobre y controle <span class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">todas sus sucursales</span></h1>
                <p class="mt-6 text-lg leading-relaxed text-slate-600">Facturas con verificación QR en 5 diseños profesionales, inventario por sucursal, pagos, bancos, cheques, préstamos e informes. Todo en un solo lugar, listo en minutos.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary px-6 py-3 text-base">Comenzar prueba gratis</a>
                    <a href="{{ route('pricing') }}" class="btn-secondary px-6 py-3 text-base">Ver planes y precios</a>
                </div>
                <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5"><x-icon name="check" class="h-4 w-4 text-emerald-500"/> Sin tarjeta de crédito</span>
                    <span class="flex items-center gap-1.5"><x-icon name="check" class="h-4 w-4 text-emerald-500"/> Multi-empresa y multi-sucursal</span>
                    <span class="flex items-center gap-1.5"><x-icon name="check" class="h-4 w-4 text-emerald-500"/> 100 % en español</span>
                </div>
            </div>
            {{-- Maqueta del panel --}}
            <div class="relative">
                <div class="rounded-2xl bg-slate-900 p-2 shadow-2xl shadow-indigo-500/20 ring-1 ring-slate-900/10">
                    <div class="flex gap-1.5 px-2 py-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span></div>
                    <div class="flex overflow-hidden rounded-xl bg-slate-50">
                        <div class="hidden w-36 shrink-0 space-y-1.5 bg-slate-900 p-3 sm:block">
                            @foreach (['Panel', 'Facturas', 'Clientes', 'Productos', 'Bancos', 'Informes'] as $i => $item)
                                <div class="rounded-md px-2 py-1.5 text-[11px] {{ $i === 0 ? 'bg-indigo-500/20 text-white' : 'text-slate-400' }}">{{ $item }}</div>
                            @endforeach
                        </div>
                        <div class="flex-1 space-y-3 p-4">
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ([['Ventas del mes', '$48.250', 'text-indigo-600'], ['Por cobrar', '$12.430', 'text-amber-600'], ['En bancos', '$91.870', 'text-emerald-600']] as [$l, $v, $c])
                                    <div class="rounded-lg bg-white p-2.5 shadow-sm"><p class="text-[9px] uppercase text-slate-400">{{ $l }}</p><p class="text-sm font-bold {{ $c }}">{{ $v }}</p></div>
                                @endforeach
                            </div>
                            <div class="rounded-lg bg-white p-3 shadow-sm">
                                <div class="flex h-28 items-end gap-1.5">
                                    @foreach ([35, 50, 42, 65, 58, 72, 60, 80, 74, 88, 70, 95] as $h)
                                        <div class="flex-1 rounded-t bg-gradient-to-t from-indigo-500 to-violet-400" style="height: {{ $h }}%"></div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="space-y-1.5 rounded-lg bg-white p-3 shadow-sm">
                                @foreach ([['FAC-00128', 'Pagada', 'bg-emerald-100 text-emerald-700'], ['FAC-00127', 'Pendiente', 'bg-amber-100 text-amber-700'], ['FAC-00126', 'Pago parcial', 'bg-sky-100 text-sky-700']] as [$n, $s, $c])
                                    <div class="flex items-center justify-between text-[11px]"><span class="font-medium text-slate-700">{{ $n }}</span><span class="rounded-full px-2 py-0.5 {{ $c }}">{{ $s }}</span></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-6 -left-6 hidden rounded-xl bg-white p-3 shadow-xl ring-1 ring-slate-100 sm:flex sm:items-center sm:gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600"><x-icon name="qr" class="h-6 w-6"/></div>
                    <div><p class="text-xs font-semibold">Factura verificada</p><p class="text-[11px] text-slate-500">Código QR auténtico</p></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Funciones --}}
    <section id="funciones" class="bg-slate-50 py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Qué incluye</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Un ERP de facturación completo, sin complicaciones</h2>
                <p class="mt-4 text-lg text-slate-500">Todo lo que una empresa necesita para vender, cobrar, comprar y controlar su dinero.</p>
            </div>
            <div class="mt-16 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modules as [$title, $desc, $icon, $gradient])
                    <div class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-1 hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br {{ $gradient }} text-white shadow-lg"><x-icon :name="$icon" class="h-6 w-6"/></div>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Diseños --}}
    <section id="disenos" class="py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">5 estilos de factura</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Facturas que hablan bien de su marca</h2>
                <p class="mt-4 text-lg text-slate-500">Moderno, clásico, corporativo, elegante y llamativo. Todos con su logo, sus colores y un código QR de verificación.</p>
            </div>
            <div class="mt-14 grid grid-cols-2 gap-5 md:grid-cols-5">
                @foreach ($styles as $key => $label)
                    @php $c = $styleColors[$key]; @endphp
                    <div class="group">
                        <div class="aspect-[3/4] overflow-hidden rounded-xl bg-white shadow-md ring-1 ring-slate-200 transition group-hover:-translate-y-1 group-hover:shadow-xl">
                            @if ($key === 'bold')<div class="flex h-1/4 items-end p-3" style="background: {{ $c }}"><span class="text-lg font-black text-white">FACTURA</span></div>
                            @elseif ($key === 'corporate')<div class="h-1/5" style="background: {{ $c }}"></div><div class="h-1" style="background:#4f46e5"></div>
                            @elseif ($key === 'modern')<div class="h-2" style="background: {{ $c }}"></div>
                            @endif
                            <div class="space-y-2 p-3">
                                <div class="flex justify-between"><div class="h-2 w-1/3 rounded" style="background: {{ $c }}"></div><div class="h-2 w-1/4 rounded bg-slate-200"></div></div>
                                @if ($key === 'classic')<div class="border-y-2 border-double border-slate-800 py-1 text-center text-[8px] font-bold tracking-widest">FACTURA</div>@endif
                                @if ($key === 'elegant')<p class="text-right font-serif text-sm italic" style="color: {{ $c }}">Factura</p>@endif
                                <div class="h-1.5 w-1/2 rounded bg-slate-100"></div>
                                <div class="mt-3 space-y-1">@for ($i = 0; $i < 5; $i++)<div class="h-1.5 rounded {{ $i === 0 ? '' : 'bg-slate-100' }}" @if ($i === 0) style="background: {{ $c }}; opacity:.85" @endif></div>@endfor</div>
                                <div class="flex items-end justify-between pt-3">
                                    <div class="grid h-7 w-7 grid-cols-3 gap-px">@foreach ([1,0,1,0,1,0,1,1,1] as $q)<span class="{{ $q ? 'bg-slate-800' : '' }}"></span>@endforeach</div>
                                    <div class="h-3 w-1/3 rounded" style="background: {{ $c }}"></div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-center text-sm font-semibold text-slate-700">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Venta rápida --}}
    <section class="bg-slate-900 py-24 text-white">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-300">Entrada rápida de facturas</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Vender en el mostrador nunca fue tan rápido</h2>
                <p class="mt-4 text-lg text-slate-300">Escanee con el lector de código de barras o escriba el nombre. Ajuste cantidades con <kbd class="rounded bg-white/10 px-1.5">+</kbd> <kbd class="rounded bg-white/10 px-1.5">−</kbd>, cobre con <kbd class="rounded bg-white/10 px-1.5">F9</kbd> e imprima. Sin tocar el ratón.</p>
                <ul class="mt-6 space-y-2 text-slate-300">
                    <li class="flex gap-2"><x-icon name="check" class="h-5 w-5 text-emerald-400"/> Listas de precios automáticas por cliente</li>
                    <li class="flex gap-2"><x-icon name="check" class="h-5 w-5 text-emerald-400"/> Cálculo de cambio y pagos parciales</li>
                    <li class="flex gap-2"><x-icon name="check" class="h-5 w-5 text-emerald-400"/> Descuento de inventario en tiempo real</li>
                </ul>
            </div>
            <div class="grid grid-cols-4 gap-3">
                @foreach (['F2' => 'Buscar producto', 'F4' => 'Cliente', 'F8' => 'Pago', 'F9' => 'Cobrar', '↑ ↓' => 'Navegar', '+ −' => 'Cantidad', 'Supr' => 'Quitar línea', 'Esc' => 'Limpiar'] as $k => $l)
                    <div class="rounded-xl bg-white/5 p-4 text-center ring-1 ring-white/10"><p class="text-xl font-bold text-indigo-300">{{ $k }}</p><p class="mt-1 text-xs text-slate-400">{{ $l }}</p></div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Precios --}}
    <section id="precios" class="py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto mb-12 max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Precios</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Planes simples y transparentes</h2>
            </div>
            @include('landing._pricing')
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="bg-slate-50 py-24">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center text-3xl font-extrabold tracking-tight text-slate-900">Preguntas frecuentes</h2>
            <div class="mt-10 space-y-3" x-data="{ open: 0 }">
                @foreach ([
                    ['¿Puedo gestionar varias sucursales?', 'Sí. Cada sucursal tiene su propio inventario, documentos y cuentas. Puede asignar usuarios a una sucursal concreta o permitirles ver todas.'],
                    ['¿Cómo funciona la verificación QR?', 'Cada documento tiene un código único. Al escanear el QR, su cliente accede a una página pública que confirma que el documento fue emitido por su empresa, con su importe y estado.'],
                    ['¿Puedo controlar qué ve cada empleado?', 'Sí. Los roles definen permisos de ver, crear, editar y eliminar en cada módulo: ventas, compras, inventario, finanzas, informes y configuración.'],
                    ['¿Qué son las fórmulas empresariales?', 'Cálculos automáticos sobre los totales: retenciones, recargos, propinas o tasas. Por ejemplo, "subtotal * 0.025" aplica una retención del 2,5 % a cada factura.'],
                    ['¿Mis datos están separados de otras empresas?', 'Sí. Cada empresa es un espacio aislado: los datos de una nunca son visibles para otra.'],
                ] as $i => [$q, $a])
                    <div class="rounded-xl bg-white ring-1 ring-slate-200">
                        <button type="button" class="flex w-full items-center justify-between px-5 py-4 text-left font-semibold" @click="open = open === {{ $i }} ? null : {{ $i }}">
                            {{ $q }} <x-icon name="chevron-down" class="h-5 w-5 text-slate-400 transition" ::class="open === {{ $i }} && 'rotate-180'"/>
                        </button>
                        <div x-show="open === {{ $i }}" x-transition class="px-5 pb-4 text-slate-600">{{ $a }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <div class="rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700 px-8 py-14 text-center text-white shadow-2xl">
                <h2 class="text-3xl font-extrabold sm:text-4xl">Empiece a facturar hoy mismo</h2>
                <p class="mx-auto mt-4 max-w-xl text-indigo-100">Cree su cuenta, configure su empresa y emita su primera factura con QR en menos de cinco minutos.</p>
                <a href="{{ route('register') }}" class="mt-8 inline-flex rounded-lg bg-white px-6 py-3 font-semibold text-indigo-700 shadow hover:bg-indigo-50">Crear cuenta gratis</a>
            </div>
        </div>
    </section>
</x-public-layout>
