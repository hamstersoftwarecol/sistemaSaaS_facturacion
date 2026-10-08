# FacturaPro · Sistema SaaS de facturación y cobros

Software de facturación profesional **multi-empresa (SaaS) y multi-sucursal**, construido con
**Laravel 13 + Breeze (Blade, Alpine.js, Tailwind CSS) y SQLite**. Funciona como un ERP ligero:
ventas, compras, inventario, tesorería, informes y un panel de administración para vender el
software por suscripción.

![Stack](https://img.shields.io/badge/Laravel-13-red) ![PHP](https://img.shields.io/badge/PHP-8.3+-777bb4) ![DB](https://img.shields.io/badge/DB-SQLite-003b57)

---

## Qué incluye

| Área | Funcionalidades |
|------|-----------------|
| **Sitio público** | Página de inicio, sección de funciones, vitrina de los 5 diseños de factura, planes y precios (mensual/anual), preguntas frecuentes y registro con alta automática de la empresa. |
| **Administración SaaS** (`/admin`) | Métricas (MRR, ARR, altas, ingresos), empresas (alta, edición, suspensión, eliminación, consumo), planes con límites, suscripciones, pagos de suscripción (aprobar/rechazar/registro manual) y métodos de pago. |
| **Empresa** | Sucursales, usuarios, roles y permisos por módulo (ver/crear/editar/eliminar), selector de sucursal activa, perfil de empresa con logotipo, suscripción y consumo del plan. |
| **Ventas** | Clientes, presupuestos, facturas, **facturas recurrentes** (programador diario), albaranes, notas de crédito, pagos recibidos con recibo de caja y **libro mayor de clientes**. Conversión presupuesto → factura/albarán, albarán → factura, factura → nota de crédito. |
| **Compras** | Proveedores, órdenes de compra (recepción de mercancía), notas de débito (devoluciones), pagos a proveedores y libro mayor de proveedores. |
| **Finanzas** | Efectivo y bancos (depósitos, retiros, transferencias), gastos, otros ingresos, cheques recibidos/emitidos con su ciclo de vida, **conciliación bancaria** y **préstamos con cuadro de amortización** (sistema francés) y pago de cuotas. |
| **Inventario** | Productos y servicios, categorías, **listas de precios** (porcentuales o precios fijos), existencias por sucursal, kardex, ajustes, transferencias entre sucursales y **etiquetas de código de barras** Code128 imprimibles. |
| **Venta rápida** | Facturación tipo TPV operada **solo con teclado** o lector de código de barras (`F2` buscar, `F4` cliente, `F8` pago, `F9` cobrar, `+/-` cantidad, `Supr` quitar). |
| **Documentos** | **5 estilos de factura**: moderno, clásico, corporativo, elegante y llamativo, con **código QR de verificación** pública (`/verificar/{código}`), impresión y descarga en **PDF** (dompdf). |
| **Informes** | Ventas, compras, impuestos (repercutido vs. soportado), pérdidas y ganancias, cuentas por cobrar y por pagar (antigüedad de saldos), inventario valorizado, flujo de caja, gastos y ventas por cliente. Exportación CSV. |
| **Configuración** | Tipos impositivos, categorías, **campos personalizados** (clientes, proveedores, productos y documentos) y **fórmulas empresariales** (retenciones, recargos, propinas…) evaluadas con un intérprete seguro. |

## Requisitos

- PHP 8.3+ con extensiones `pdo_sqlite`, `gd`, `mbstring`, `intl`, `zip`
- Composer 2
- Node.js 18+ y npm

## Instalación

```bash
git clone https://github.com/hamstersoftwarecol/sistema-SaaS-completo-de-facturaci-n-y-cobros.git
cd sistema-SaaS-completo-de-facturaci-n-y-cobros
composer run setup      # instala dependencias, crea .env y la BD SQLite, migra con datos demo y compila assets
php artisan serve       # http://127.0.0.1:8000
```

Instalación manual equivalente:

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
```

Para desarrollo con recarga en caliente: `npm run dev` en otra terminal.

### Cuentas de demostración

Todas usan la contraseña `password`.

| Correo | Perfil |
|--------|--------|
| `admin@facturapro.test` | Super administrador de la plataforma (`/admin`) |
| `demo@facturapro.test` | Propietaria de *Distribuidora Andina S.A.S.* (plan Profesional, 2 sucursales, datos de 7 meses) |
| `vendedor@facturapro.test` | Rol **Vendedor** (asignado a Sede Centro) |
| `contador@facturapro.test` | Rol **Contador** |
| `almacen@facturapro.test` | Rol **Almacén** (asignado a Sucursal Norte) |
| `ferreteria@facturapro.test` | Segunda empresa (plan Básico en prueba, con un pago pendiente de aprobar) |

### Tareas programadas

Añada el programador de Laravel al cron del servidor:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

- `invoices:recurring` (diario 06:00): emite las facturas recurrentes vencidas de todas las empresas.
- `subscriptions:expire` (diario 00:30): marca como vencidas las suscripciones cuyo periodo terminó.

## Arquitectura

```
app/
├── Models/Concerns/BelongsToCompany.php   # Aislamiento multi-tenant (scope global por company_id)
├── Models/Concerns/BelongsToBranch.php    # Sucursal y usuario automáticos + filtro por sucursal activa
├── Models/Concerns/SyncsAccountTransaction.php  # Pagos, gastos, cheques y cuotas → movimientos de caja/banco
├── Models/Document.php                    # Un modelo para los 6 tipos de documento comercial
├── Services/
│   ├── DocumentService.php      # Cálculo de líneas, impuestos, fórmulas, numeración, saldos y conversiones
│   ├── InventoryService.php     # Movimientos de stock por sucursal (ventas, compras, devoluciones, ajustes)
│   ├── CompanyProvisioner.php   # Alta de empresa: sucursal, roles, impuestos, categorías, caja y suscripción
│   ├── SubscriptionService.php  # Planes, límites de uso, activación y aprobación de pagos
│   ├── LedgerService.php        # Libro mayor de clientes y proveedores
│   ├── ReportService.php        # Consultas de informes
│   ├── LoanService.php          # Cuadro de amortización
│   └── RecurringInvoiceService.php
├── Support/
│   ├── Tenant.php               # Empresa y sucursal activas
│   ├── FormulaEvaluator.php     # Intérprete de fórmulas sin eval()
│   ├── Permissions.php          # Catálogo de permisos (config/permissions.php)
│   ├── Codes.php                # QR (bacon/bacon-qr-code) y códigos de barras (picqer)
│   └── Pdf.php                  # Generación de PDF con dompdf
└── Http/Middleware/             # tenant, subscribed, superadmin
resources/views/documents/templates/  # modern, classic, corporate, elegant, bold
```

### Multi-tenant y seguridad

- Todas las tablas de negocio tienen `company_id`; el scope global `BelongsToCompany` filtra cada consulta
  por la empresa del usuario autenticado y las reglas de validación `exists` se restringen a la empresa.
- Los permisos tienen la forma `modulo.accion` (por ejemplo `invoices.create`) y se comprueban con
  `Gate::before`; cada controlador declara su módulo y las acciones REST se protegen automáticamente.
- Los usuarios asignados a una sucursal solo ven y operan en esa sucursal.
- Si la suscripción vence, la empresa solo puede acceder a la página de suscripción hasta renovar.

### Fórmulas empresariales

Se aplican al guardar documentos de venta, compra o ambos. Variables: `subtotal`, `gross`, `discount`,
`tax`, `total`, `items`, `quantity` y el código de cualquier fórmula anterior. Operadores `+ - * / % ^`,
comparaciones y funciones `min`, `max`, `round`, `abs`, `ceil`, `floor`, `if`.

```text
round(subtotal * 0.025, 2)          → retención del 2,5 %
if(subtotal < 100, 8, 0)            → envío gratis desde 100
max(0, (subtotal - 1000) * 0.01)    → tasa sobre el excedente
```

## Pruebas

```bash
php artisan test
```

Incluye pruebas de registro y alta de empresas, aislamiento entre empresas, permisos por rol,
suscripciones y límites de plan, ciclo completo de facturación (totales, fórmulas, stock, pagos,
notas de crédito), PDF y verificación QR, venta rápida, el intérprete de fórmulas y una prueba que
recorre **todas las pantallas** de la empresa y del panel de administración con los datos de demostración.

## Licencia

MIT.
