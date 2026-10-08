<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\BarcodeLabelController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Documents;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\Payments;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuickInvoiceController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\RecurringInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitio público
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingController::class, 'home'])->name('home');
Route::get('/precios', [LandingController::class, 'pricing'])->name('pricing');
Route::get('/verificar/{code}', [VerificationController::class, 'show'])->name('verify.document');

/*
|--------------------------------------------------------------------------
| Perfil de usuario (Breeze)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/perfil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Panel de la empresa (tenant)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('/sucursal-activa', BranchSwitchController::class)->name('branch.switch');

    // Suscripción: accesible aunque el plan haya vencido.
    Route::get('/suscripcion', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/suscripcion/plan/{plan:slug}', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/suscripcion/plan/{plan:slug}', [SubscriptionController::class, 'pay'])->name('subscription.pay');

    Route::middleware('subscribed')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // Ventas
        Route::get('/clientes/{customer}/estado-de-cuenta', [CustomerController::class, 'ledger'])->name('customers.ledger');
        Route::resource('clientes', CustomerController::class)->names('customers')->parameters(['clientes' => 'customer']);

        Route::get('/facturas/rapida', [QuickInvoiceController::class, 'create'])->name('invoices.quick');
        Route::post('/facturas/rapida', [QuickInvoiceController::class, 'store'])->name('invoices.quick.store');

        $documentRoutes = [
            'presupuestos' => ['quotes', Documents\QuoteController::class],
            'facturas' => ['invoices', Documents\InvoiceController::class],
            'albaranes' => ['delivery-notes', Documents\DeliveryNoteController::class],
            'notas-de-credito' => ['credit-notes', Documents\CreditNoteController::class],
            'ordenes-de-compra' => ['purchase-orders', Documents\PurchaseOrderController::class],
            'notas-de-debito' => ['debit-notes', Documents\DebitNoteController::class],
        ];

        foreach ($documentRoutes as $uri => [$name, $controller]) {
            Route::post("/{$uri}/calcular", [$controller, 'calculate'])->name("{$name}.calculate");
            Route::get("/{$uri}/{document}/imprimir", [$controller, 'print'])->name("{$name}.print");
            Route::get("/{$uri}/{document}/pdf", [$controller, 'pdf'])->name("{$name}.pdf");
            Route::post("/{$uri}/{document}/convertir/{target}", [$controller, 'convert'])->name("{$name}.convert");
            Route::post("/{$uri}/{document}/estado", [$controller, 'status'])->name("{$name}.status");
            Route::post("/{$uri}/{document}/duplicar", [$controller, 'duplicate'])->name("{$name}.duplicate");
            Route::resource($uri, $controller)->names($name)->parameters([$uri => 'document']);
        }

        Route::post('/facturas-recurrentes/{recurring_invoice}/generar', [RecurringInvoiceController::class, 'generate'])->name('recurring-invoices.generate');
        Route::resource('facturas-recurrentes', RecurringInvoiceController::class)->names('recurring-invoices')->parameters(['facturas-recurrentes' => 'recurring_invoice']);

        Route::get('/pagos-recibidos/{payment}/recibo', [Payments\ReceivedPaymentController::class, 'receipt'])->name('payments-received.receipt');
        Route::resource('pagos-recibidos', Payments\ReceivedPaymentController::class)->names('payments-received')->parameters(['pagos-recibidos' => 'payment']);

        // Compras
        Route::get('/proveedores/{supplier}/estado-de-cuenta', [SupplierController::class, 'ledger'])->name('suppliers.ledger');
        Route::resource('proveedores', SupplierController::class)->names('suppliers')->parameters(['proveedores' => 'supplier']);
        Route::get('/pagos-a-proveedores/{payment}/recibo', [Payments\MadePaymentController::class, 'receipt'])->name('payments-made.receipt');
        Route::resource('pagos-a-proveedores', Payments\MadePaymentController::class)->names('payments-made')->parameters(['pagos-a-proveedores' => 'payment']);

        // Inventario
        Route::get('/productos/buscar', [ProductController::class, 'lookup'])->name('products.lookup');
        Route::resource('productos', ProductController::class)->names('products')->parameters(['productos' => 'product']);
        Route::resource('listas-de-precios', PriceListController::class)->names('price-lists')->parameters(['listas-de-precios' => 'price_list']);
        Route::get('/inventario', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventario/movimientos', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get('/inventario/ajuste', [InventoryController::class, 'adjustForm'])->name('inventory.adjust');
        Route::post('/inventario/ajuste', [InventoryController::class, 'adjust'])->name('inventory.adjust.store');
        Route::get('/inventario/transferencia', [InventoryController::class, 'transferForm'])->name('inventory.transfer');
        Route::post('/inventario/transferencia', [InventoryController::class, 'transfer'])->name('inventory.transfer.store');
        Route::get('/etiquetas', [BarcodeLabelController::class, 'index'])->name('labels.index');
        Route::post('/etiquetas/imprimir', [BarcodeLabelController::class, 'print'])->name('labels.print');

        // Finanzas
        Route::get('/cuentas/transferencia', [AccountController::class, 'transferForm'])->name('accounts.transfer');
        Route::post('/cuentas/transferencia', [AccountController::class, 'transfer'])->name('accounts.transfer.store');
        Route::post('/cuentas/{account}/movimiento', [AccountController::class, 'storeTransaction'])->name('accounts.transactions.store');
        Route::delete('/cuentas/movimientos/{transaction}', [AccountController::class, 'destroyTransaction'])->name('accounts.transactions.destroy');
        Route::resource('cuentas', AccountController::class)->names('accounts')->parameters(['cuentas' => 'account']);
        Route::resource('gastos', ExpenseController::class)->names('expenses')->parameters(['gastos' => 'expense'])->except('show');
        Route::resource('ingresos', IncomeController::class)->names('incomes')->parameters(['ingresos' => 'income'])->except('show');
        Route::post('/cheques/{cheque}/estado', [ChequeController::class, 'status'])->name('cheques.status');
        Route::resource('cheques', ChequeController::class)->names('cheques')->except('show');
        Route::resource('conciliaciones', ReconciliationController::class)->names('reconciliations')
            ->parameters(['conciliaciones' => 'reconciliation'])->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::post('/prestamos/cuotas/{installment}/pagar', [LoanController::class, 'payInstallment'])->name('loans.installments.pay');
        Route::post('/prestamos/cuotas/{installment}/revertir', [LoanController::class, 'unpayInstallment'])->name('loans.installments.unpay');
        Route::resource('prestamos', LoanController::class)->names('loans')->parameters(['prestamos' => 'loan']);

        // Informes
        Route::get('/informes', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/informes/{report}', [ReportController::class, 'show'])->name('reports.show')
            ->whereIn('report', array_keys(ReportController::REPORTS));

        // Configuración
        Route::get('/configuracion/empresa', [CompanySettingsController::class, 'edit'])->name('settings.company');
        Route::put('/configuracion/empresa', [CompanySettingsController::class, 'update'])->name('settings.company.update');
        Route::get('/configuracion/facturas', [CompanySettingsController::class, 'invoice'])->name('settings.invoice');
        Route::put('/configuracion/facturas', [CompanySettingsController::class, 'updateInvoice'])->name('settings.invoice.update');
        Route::get('/configuracion/facturas/vista-previa/{style}', [CompanySettingsController::class, 'preview'])->name('settings.invoice.preview');
        Route::resource('configuracion/sucursales', BranchController::class)->names('branches')->parameters(['sucursales' => 'branch'])->except('show');
        Route::resource('configuracion/usuarios', UserController::class)->names('users')->parameters(['usuarios' => 'user'])->except('show');
        Route::resource('configuracion/roles', RoleController::class)->names('roles')->except('show');
        Route::resource('configuracion/impuestos', TaxRateController::class)->names('tax-rates')->parameters(['impuestos' => 'tax_rate'])->except('show');
        Route::resource('configuracion/categorias', CategoryController::class)->names('categories')->parameters(['categorias' => 'category'])->except('show');
        Route::resource('configuracion/campos-personalizados', CustomFieldController::class)->names('custom-fields')->parameters(['campos-personalizados' => 'custom_field'])->except('show');
        Route::resource('configuracion/formulas', FormulaController::class)->names('formulas')->except('show');
    });
});

/*
|--------------------------------------------------------------------------
| Administración de la plataforma SaaS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::post('/empresas/{company}/estado', [Admin\CompanyController::class, 'toggle'])->name('companies.toggle');
    Route::resource('empresas', Admin\CompanyController::class)->names('companies')->parameters(['empresas' => 'company']);
    Route::resource('planes', Admin\PlanController::class)->names('plans')->parameters(['planes' => 'plan'])->except('show');
    Route::resource('suscripciones', Admin\SubscriptionController::class)->names('subscriptions')
        ->parameters(['suscripciones' => 'subscription'])->only(['index', 'edit', 'update']);
    Route::post('/pagos/{payment}/aprobar', [Admin\SubscriptionPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/pagos/{payment}/rechazar', [Admin\SubscriptionPaymentController::class, 'reject'])->name('payments.reject');
    Route::resource('pagos', Admin\SubscriptionPaymentController::class)->names('payments')
        ->parameters(['pagos' => 'payment'])->only(['index', 'create', 'store']);
    Route::resource('metodos-de-pago', Admin\PaymentMethodController::class)->names('payment-methods')
        ->parameters(['metodos-de-pago' => 'payment_method'])->except('show');
});

require __DIR__.'/auth.php';
