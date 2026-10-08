<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * Recorre todas las pantallas GET de la aplicación con los datos de demostración
 * para detectar errores de renderizado en vistas y controladores.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_every_company_screen_renders_for_the_owner(): void
    {
        $owner = Models\User::where('email', 'demo@facturapro.test')->firstOrFail();
        $this->actingAs($owner);

        $failures = $this->visitRoutes(fn (string $name) => ! str_starts_with($name, 'admin.')
            && ! in_array($name, ['login', 'register', 'password.request', 'password.reset', 'verification.notice', 'verification.verify', 'password.confirm'], true), $owner);

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_every_admin_screen_renders_for_the_super_admin(): void
    {
        $admin = Models\User::where('email', 'admin@facturapro.test')->firstOrFail();
        $this->actingAs($admin);

        $failures = $this->visitRoutes(fn (string $name) => str_starts_with($name, 'admin.') || $name === 'profile.edit', $admin);

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_restricted_roles_can_render_the_layout(): void
    {
        foreach (['vendedor@facturapro.test', 'contador@facturapro.test', 'almacen@facturapro.test'] as $email) {
            $user = Models\User::where('email', $email)->firstOrFail();
            $this->actingAs($user)->get(route('dashboard'))->assertOk();
        }
    }

    protected function visitRoutes(callable $filter, Models\User $user): array
    {
        Tenant::set($user->company);
        $params = $this->parameterValues();
        Tenant::forget();

        $failures = [];
        $visited = 0;

        foreach (RouteFacade::getRoutes() as $route) {
            /** @var Route $route */
            $name = $route->getName();
            if (! $name || ! in_array('GET', $route->methods(), true) || ! $filter($name)) {
                continue;
            }
            if (str_starts_with($route->uri(), '_') || str_contains($route->uri(), 'storage/') || $name === 'products.lookup') {
                continue;
            }

            $values = [];
            foreach ($route->parameterNames() as $param) {
                $values[$param] = $params[$this->paramKey($name, $param)] ?? $params[$param] ?? null;
                if ($values[$param] === null) {
                    $failures[] = "Sin valor para el parámetro {$param} en {$name}";

                    continue 2;
                }
            }

            $url = route($name, $values);
            $response = $this->get($url);
            $visited++;

            if ($response->getStatusCode() >= 400 || ($response->getStatusCode() >= 300 && ! in_array($name, ['dashboard', 'home'], true) && $response->isRedirect() && ! str_contains($name, 'verify'))) {
                $failures[] = "{$name} ({$url}) → HTTP {$response->getStatusCode()}"
                    .($response->exception ? ': '.$response->exception->getMessage() : '');
            }
        }

        $this->assertGreaterThan(10, $visited);

        return $failures;
    }

    protected function paramKey(string $routeName, string $param): string
    {
        return $param === 'document' || $param === 'payment' ? explode('.', $routeName)[0].'.'.$param : $param;
    }

    protected function parameterValues(): array
    {
        $doc = fn (string $type) => Models\Document::ofType($type)->latest('id')->first();

        return [
            'quotes.document' => $doc('quote'),
            'invoices.document' => $doc('invoice'),
            'delivery-notes.document' => $doc('delivery_note'),
            'credit-notes.document' => $doc('credit_note'),
            'purchase-orders.document' => $doc('purchase_order'),
            'debit-notes.document' => $doc('debit_note'),
            'payments-received.payment' => Models\Payment::where('direction', 'in')->first(),
            'payments-made.payment' => Models\Payment::where('direction', 'out')->first(),
            'customer' => Models\Customer::first(),
            'supplier' => Models\Supplier::first(),
            'product' => Models\Product::first(),
            'price_list' => Models\PriceList::first(),
            'recurring_invoice' => Models\RecurringInvoice::first(),
            'account' => Models\Account::where('type', 'bank')->first(),
            'expense' => Models\Expense::first(),
            'income' => Models\Income::first(),
            'cheque' => Models\Cheque::first(),
            'loan' => Models\Loan::first(),
            'branch' => Models\Branch::first(),
            'user' => Models\User::where('company_id', Tenant::id())->first(),
            'role' => Models\Role::first(),
            'tax_rate' => Models\TaxRate::first(),
            'category' => Models\Category::first(),
            'custom_field' => Models\CustomField::first(),
            'formula' => Models\Formula::first(),
            'plan' => Models\Plan::first(),
            'style' => 'elegant',
            'report' => array_key_first(ReportController::REPORTS),
            'code' => Models\Document::first()?->verification_code,
            'company' => Models\Company::first(),
            'subscription' => Models\Subscription::withoutGlobalScopes()->first(),
            'payment_method' => Models\PaymentMethod::first(),
            'reconciliation' => Models\Reconciliation::first(),
        ];
    }

    public function test_all_reports_render_and_export(): void
    {
        $owner = Models\User::where('email', 'demo@facturapro.test')->firstOrFail();
        $this->actingAs($owner);

        foreach (array_keys(ReportController::REPORTS) as $report) {
            $this->get(route('reports.show', $report))->assertOk();
            $this->get(route('reports.show', [$report, 'export' => 'csv']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        }
    }

    public function test_reconciliation_and_document_pdfs(): void
    {
        $owner = Models\User::where('email', 'demo@facturapro.test')->firstOrFail();
        $this->actingAs($owner);
        Tenant::set($owner->company);
        $account = Models\Account::where('type', 'bank')->first();
        $ids = $account->transactions()->whereNull('reconciled_at')->limit(3)->pluck('id')->all();
        Tenant::forget();

        $this->get(route('reconciliations.create', ['account_id' => $account->id, 'statement_date' => today()->toDateString(), 'statement_balance' => 1000]))->assertOk();
        $this->post(route('reconciliations.store'), [
            'account_id' => $account->id, 'statement_date' => today()->toDateString(), 'statement_balance' => 1000, 'transactions' => $ids,
        ])->assertRedirect();

        $reconciliation = Models\Reconciliation::withoutGlobalScopes()->latest('id')->firstOrFail();
        $this->get(route('reconciliations.show', $reconciliation))->assertOk();
        $this->assertSame(3, $reconciliation->transactions_count);

        foreach (['quote', 'invoice', 'delivery_note', 'credit_note', 'purchase_order', 'debit_note'] as $type) {
            $document = Models\Document::withoutGlobalScopes()->where('company_id', $owner->company_id)->where('type', $type)->first();
            $response = $this->get(route($document->routeName('pdf'), $document));
            $response->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }
}
