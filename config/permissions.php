<?php

/*
|--------------------------------------------------------------------------
| Permisos por módulo
|--------------------------------------------------------------------------
| Cada módulo define las acciones disponibles. Las claves de permiso tienen
| la forma "modulo.accion" (p. ej. "invoices.create") y se asignan a roles.
*/

$crud = ['view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar', 'delete' => 'Eliminar'];

return [
    'groups' => [
        'General' => [
            'dashboard' => ['label' => 'Panel de control', 'actions' => ['view' => 'Ver']],
            'reports' => ['label' => 'Informes', 'actions' => ['view' => 'Ver']],
        ],
        'Ventas' => [
            'customers' => ['label' => 'Clientes', 'actions' => $crud],
            'quotes' => ['label' => 'Presupuestos', 'actions' => $crud],
            'invoices' => ['label' => 'Facturas', 'actions' => $crud],
            'recurring_invoices' => ['label' => 'Facturas recurrentes', 'actions' => $crud],
            'delivery_notes' => ['label' => 'Albaranes', 'actions' => $crud],
            'credit_notes' => ['label' => 'Notas de crédito', 'actions' => $crud],
            'payments_received' => ['label' => 'Pagos recibidos', 'actions' => $crud],
        ],
        'Compras' => [
            'suppliers' => ['label' => 'Proveedores', 'actions' => $crud],
            'purchase_orders' => ['label' => 'Órdenes de compra', 'actions' => $crud],
            'debit_notes' => ['label' => 'Notas de débito', 'actions' => $crud],
            'payments_made' => ['label' => 'Pagos a proveedores', 'actions' => $crud],
        ],
        'Inventario' => [
            'products' => ['label' => 'Productos', 'actions' => $crud],
            'price_lists' => ['label' => 'Listas de precios', 'actions' => $crud],
            'inventory' => ['label' => 'Inventario', 'actions' => ['view' => 'Ver', 'create' => 'Ajustar / transferir']],
        ],
        'Finanzas' => [
            'accounts' => ['label' => 'Efectivo y bancos', 'actions' => $crud],
            'expenses' => ['label' => 'Gastos', 'actions' => $crud],
            'incomes' => ['label' => 'Otros ingresos', 'actions' => $crud],
            'cheques' => ['label' => 'Cheques', 'actions' => $crud],
            'reconciliations' => ['label' => 'Conciliación bancaria', 'actions' => ['view' => 'Ver', 'create' => 'Conciliar', 'delete' => 'Eliminar']],
            'loans' => ['label' => 'Préstamos y cuotas', 'actions' => $crud],
        ],
        'Configuración' => [
            'branches' => ['label' => 'Sucursales', 'actions' => $crud],
            'users' => ['label' => 'Usuarios', 'actions' => $crud],
            'roles' => ['label' => 'Roles y permisos', 'actions' => $crud],
            'settings' => ['label' => 'Perfil, impuestos, campos y fórmulas', 'actions' => ['view' => 'Ver', 'edit' => 'Editar']],
            'subscription' => ['label' => 'Suscripción', 'actions' => ['view' => 'Ver', 'edit' => 'Cambiar plan']],
        ],
    ],

    /*
    | Roles creados automáticamente para cada empresa nueva.
    | "*" concede todos los permisos; "modulo.*" todas las acciones del módulo.
    */
    'default_roles' => [
        'Administrador' => [
            'description' => 'Acceso total a la empresa',
            'permissions' => ['*'],
        ],
        'Vendedor' => [
            'description' => 'Ventas, clientes y cobros',
            'permissions' => [
                'dashboard.view', 'customers.*', 'quotes.*', 'invoices.view', 'invoices.create', 'invoices.edit',
                'delivery_notes.*', 'payments_received.view', 'payments_received.create', 'products.view',
                'inventory.view', 'recurring_invoices.view',
            ],
        ],
        'Contador' => [
            'description' => 'Finanzas, informes y cuentas por cobrar/pagar',
            'permissions' => [
                'dashboard.view', 'reports.view', 'customers.view', 'suppliers.view', 'invoices.view',
                'credit_notes.*', 'debit_notes.*', 'payments_received.*', 'payments_made.*', 'accounts.*',
                'expenses.*', 'incomes.*', 'cheques.*', 'reconciliations.*', 'loans.*', 'purchase_orders.view',
            ],
        ],
        'Almacén' => [
            'description' => 'Productos, inventario y compras',
            'permissions' => [
                'dashboard.view', 'products.*', 'price_lists.view', 'inventory.*', 'suppliers.*',
                'purchase_orders.*', 'debit_notes.view', 'delivery_notes.view',
            ],
        ],
    ],
];
