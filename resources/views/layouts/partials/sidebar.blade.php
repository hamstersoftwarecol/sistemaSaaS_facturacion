@php
    $groups = [
        null => [
            ['Panel de control', 'dashboard', 'home', null, 'dashboard'],
        ],
        'Ventas' => [
            ['Clientes', 'customers.index', 'users', 'customers.view', 'customers.*'],
            ['Presupuestos', 'quotes.index', 'clipboard', 'quotes.view', 'quotes.*'],
            ['Facturas', 'invoices.index', 'document', 'invoices.view', ['invoices.index', 'invoices.show', 'invoices.create', 'invoices.edit', 'invoices.print']],
            ['Venta rápida (teclado)', 'invoices.quick', 'bolt', 'invoices.create', 'invoices.quick'],
            ['Facturas recurrentes', 'recurring-invoices.index', 'refresh', 'recurring_invoices.view', 'recurring-invoices.*'],
            ['Albaranes', 'delivery-notes.index', 'truck', 'delivery_notes.view', 'delivery-notes.*'],
            ['Notas de crédito', 'credit-notes.index', 'receipt-refund', 'credit_notes.view', 'credit-notes.*'],
            ['Pagos recibidos', 'payments-received.index', 'inbox', 'payments_received.view', 'payments-received.*'],
        ],
        'Compras' => [
            ['Proveedores', 'suppliers.index', 'store', 'suppliers.view', 'suppliers.*'],
            ['Órdenes de compra', 'purchase-orders.index', 'shopping-cart', 'purchase_orders.view', 'purchase-orders.*'],
            ['Notas de débito', 'debit-notes.index', 'receipt-tax', 'debit_notes.view', 'debit-notes.*'],
            ['Pagos a proveedores', 'payments-made.index', 'send', 'payments_made.view', 'payments-made.*'],
        ],
        'Inventario' => [
            ['Productos', 'products.index', 'cube', 'products.view', 'products.*'],
            ['Listas de precios', 'price-lists.index', 'tag', 'price_lists.view', 'price-lists.*'],
            ['Existencias', 'inventory.index', 'archive', 'inventory.view', ['inventory.index', 'inventory.movements', 'inventory.adjust', 'inventory.transfer']],
            ['Etiquetas de código de barras', 'labels.index', 'barcode', 'products.view', 'labels.*'],
        ],
        'Finanzas' => [
            ['Efectivo y bancos', 'accounts.index', 'bank', 'accounts.view', 'accounts.*'],
            ['Gastos', 'expenses.index', 'trending-down', 'expenses.view', 'expenses.*'],
            ['Otros ingresos', 'incomes.index', 'trending-up', 'incomes.view', 'incomes.*'],
            ['Cheques', 'cheques.index', 'cheque', 'cheques.view', 'cheques.*'],
            ['Conciliación bancaria', 'reconciliations.index', 'scale', 'reconciliations.view', 'reconciliations.*'],
            ['Préstamos y cuotas', 'loans.index', 'dollar', 'loans.view', 'loans.*'],
        ],
        'Análisis' => [
            ['Informes', 'reports.index', 'chart', 'reports.view', 'reports.*'],
        ],
        'Configuración' => [
            ['Perfil de la empresa', 'settings.company', 'building', 'settings.view', 'settings.company'],
            ['Diseño de facturas', 'settings.invoice', 'swatch', 'settings.view', 'settings.invoice*'],
            ['Sucursales', 'branches.index', 'map-pin', 'branches.view', 'branches.*'],
            ['Usuarios', 'users.index', 'user', 'users.view', 'users.*'],
            ['Roles y permisos', 'roles.index', 'shield', 'roles.view', 'roles.*'],
            ['Tipos impositivos', 'tax-rates.index', 'receipt-tax', 'settings.view', 'tax-rates.*'],
            ['Categorías', 'categories.index', 'list', 'settings.view', 'categories.*'],
            ['Campos personalizados', 'custom-fields.index', 'adjustments', 'settings.view', 'custom-fields.*'],
            ['Fórmulas empresariales', 'formulas.index', 'calculator', 'settings.view', 'formulas.*'],
            ['Suscripción', 'subscription.index', 'sparkles', null, 'subscription.*'],
        ],
    ];
@endphp

@foreach ($groups as $group => $items)
    @php $visible = collect($items)->filter(fn ($i) => ! $i[3] || auth()->user()->can($i[3])); @endphp
    @if ($visible->isNotEmpty())
        @if ($group)
            <p class="nav-group">{{ $group }}</p>
        @else
            <div class="pt-4"></div>
        @endif
        <div class="space-y-0.5">
            @foreach ($visible as [$label, $route, $icon, $permission, $pattern])
                <a href="{{ route($route) }}" class="nav-item {{ request()->routeIs(...(array) $pattern) ? 'nav-item-active' : '' }}">
                    <x-icon :name="$icon" class="h-[18px] w-[18px] opacity-80"/>
                    <span class="truncate">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endforeach
