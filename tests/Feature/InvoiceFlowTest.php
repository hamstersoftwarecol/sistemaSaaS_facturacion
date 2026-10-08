<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\Document;
use App\Models\Formula;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompanies;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use CreatesCompanies, RefreshDatabase;

    public function test_invoice_lifecycle_updates_totals_stock_payments_and_credit_notes(): void
    {
        $owner = $this->createCompanyOwner();
        $data = $this->catalog($owner);
        $this->actingAs($owner);

        // Fórmula de recargo del 1 % sobre el subtotal en ventas.
        $this->asTenant($owner, fn () => Formula::create(['name' => 'Recargo', 'code' => 'recargo', 'expression' => 'subtotal * 0.01', 'applies_to' => 'sales', 'action' => 'add']));

        $response = $this->post(route('invoices.store'), [
            'customer_id' => $data['customer']->id,
            'date' => today()->toDateString(),
            'due_date' => today()->addDays(30)->toDateString(),
            'affects_stock' => 1,
            'items' => [
                ['product_id' => $data['product']->id, 'description' => 'Producto de prueba', 'quantity' => 3, 'unit_price' => 100, 'discount_percent' => 10, 'tax_rate_id' => $data['tax']->id],
                ['description' => 'Servicio libre', 'quantity' => 1, 'unit_price' => 50, 'tax_rate_id' => ''],
                ['description' => '', 'quantity' => 1, 'unit_price' => 0],
            ],
        ]);

        $invoice = Document::withoutGlobalScopes()->where('type', 'invoice')->firstOrFail();
        $response->assertRedirect(route('invoices.show', $invoice));

        // 3 x 100 - 10 % = 270 + 50 = 320; IVA 19 % sobre 270 = 51.30; recargo 3.20.
        $this->assertSame('320.00', $invoice->subtotal);
        $this->assertSame('51.30', $invoice->tax_total);
        $this->assertSame('3.20', $invoice->adjustments_total);
        $this->assertSame('374.50', $invoice->total);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertStringStartsWith('FAC-', $invoice->number);
        $this->assertCount(2, $invoice->items);

        $product = Product::withoutGlobalScopes()->find($data['product']->id);
        $this->assertSame(47.0, $product->stockIn());

        // Pago parcial
        $this->post(route('payments-received.store'), [
            'customer_id' => $data['customer']->id, 'document_id' => $invoice->id, 'account_id' => $data['account']->id,
            'method' => 'cash', 'amount' => 100, 'date' => today()->toDateString(),
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertSame('274.50', $invoice->balance);
        $this->assertSame(100.0, Account::withoutGlobalScopes()->find($data['account']->id)->balance());

        // Nota de crédito generada desde la factura: devuelve stock y reduce saldo.
        $this->post(route('invoices.convert', [$invoice, 'credit_note']))->assertRedirect();
        $credit = Document::withoutGlobalScopes()->where('type', 'credit_note')->firstOrFail();
        $this->assertSame('applied', $credit->status);
        $this->assertSame(50.0, $product->fresh()->stockIn());

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
        $this->assertLessThanOrEqual(0, (float) $invoice->balance);

        // Eliminar el pago revierte el movimiento de caja.
        $payment = Payment::withoutGlobalScopes()->first();
        $this->delete(route('payments-received.destroy', $payment))->assertRedirect();
        $this->assertSame(0.0, Account::withoutGlobalScopes()->find($data['account']->id)->balance());
    }

    public function test_quote_converts_to_invoice_and_documents_render_in_every_style(): void
    {
        $owner = $this->createCompanyOwner();
        $data = $this->catalog($owner);
        $this->actingAs($owner);

        $this->post(route('quotes.store'), [
            'customer_id' => $data['customer']->id,
            'date' => today()->toDateString(),
            'status' => 'sent',
            'items' => [['product_id' => $data['product']->id, 'quantity' => 2, 'unit_price' => 100, 'tax_rate_id' => $data['tax']->id]],
        ])->assertRedirect();

        $quote = Document::withoutGlobalScopes()->where('type', 'quote')->firstOrFail();
        $this->assertSame(50.0, Product::withoutGlobalScopes()->find($data['product']->id)->stockIn(), 'Los presupuestos no mueven inventario');

        $this->post(route('quotes.convert', [$quote, 'invoice']))->assertRedirect();
        $this->assertSame('converted', $quote->fresh()->status);
        $invoice = Document::withoutGlobalScopes()->where('type', 'invoice')->firstOrFail();
        $this->assertSame($quote->id, $invoice->parent_id);

        foreach (array_keys(Company::INVOICE_STYLES) as $style) {
            $this->get(route('invoices.print', [$invoice, 'style' => $style]))->assertOk()->assertSee($invoice->number);
        }

        $pdf = $this->get(route('invoices.pdf', [$invoice, 'style' => 'corporate']));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        // Verificación pública por QR (sin sesión).
        auth()->logout();
        $this->get(route('verify.document', $invoice->verification_code))->assertOk()->assertSee('Documento auténtico')->assertSee($invoice->number);
        $this->get(route('verify.document', 'codigo-falso'))->assertOk()->assertSee('Documento no encontrado');
    }

    public function test_purchase_order_adds_stock_only_when_received(): void
    {
        $owner = $this->createCompanyOwner();
        $data = $this->catalog($owner);
        $supplier = $this->asTenant($owner, fn () => Supplier::create(['name' => 'Proveedor']));
        $this->actingAs($owner);

        $this->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id, 'date' => today()->toDateString(), 'status' => 'ordered',
            'items' => [['product_id' => $data['product']->id, 'quantity' => 10, 'unit_price' => 60]],
        ])->assertRedirect();

        $po = Document::withoutGlobalScopes()->where('type', 'purchase_order')->firstOrFail();
        $product = Product::withoutGlobalScopes()->find($data['product']->id);
        $this->assertSame(50.0, $product->stockIn());

        $this->post(route('purchase-orders.status', $po), ['status' => 'received'])->assertRedirect();
        $this->assertSame(60.0, $product->stockIn());
        $this->assertSame(600.0, $supplier->fresh()->balance());
    }

    public function test_quick_invoice_endpoint_creates_paid_invoice(): void
    {
        $owner = $this->createCompanyOwner();
        $data = $this->catalog($owner);
        $this->actingAs($owner);

        $this->get(route('invoices.quick'))->assertOk()->assertSee('Venta rápida');
        $this->getJson(route('products.lookup', ['q' => 'P-1']))->assertOk()->assertJsonPath('exact', true);

        $response = $this->postJson(route('invoices.quick.store'), [
            'customer_id' => $data['customer']->id,
            'items' => [['product_id' => $data['product']->id, 'quantity' => 2, 'unit_price' => 100, 'tax_rate_id' => $data['tax']->id]],
            'paid_amount' => 500,
            'account_id' => $data['account']->id,
            'method' => 'cash',
        ]);

        $response->assertOk()->assertJsonStructure(['message', 'number', 'print_url']);
        $invoice = Document::withoutGlobalScopes()->where('type', 'invoice')->firstOrFail();
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('238.00', $invoice->amount_paid);
    }
}
