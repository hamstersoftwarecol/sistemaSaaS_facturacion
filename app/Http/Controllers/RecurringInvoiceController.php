<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\TaxRate;
use App\Services\DocumentService;
use App\Services\RecurringInvoiceService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecurringInvoiceController extends ModuleController
{
    protected static string $module = 'recurring_invoices';

    protected static array $extraActions = ['generate' => 'create'];

    public function index(): View
    {
        return view('recurring-invoices.index', [
            'recurrings' => RecurringInvoice::forCurrentBranch()->with('customer')->withCount('invoices')->orderBy('next_date')->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        return view('recurring-invoices.form', $this->formData(new RecurringInvoice([
            'frequency' => 'monthly', 'interval' => 1, 'start_date' => today(), 'due_days' => 30, 'status' => 'active',
            'customer_id' => $request->integer('customer_id') ?: null,
            'terms' => Tenant::company()->invoice_terms,
        ])));
    }

    public function store(Request $request, DocumentService $documents): RedirectResponse
    {
        $data = $this->validated($request, $documents);
        $data['next_date'] = $data['start_date'];
        $recurring = RecurringInvoice::create($data);

        return redirect()->route('recurring-invoices.show', $recurring)->with('success', 'Factura recurrente programada.');
    }

    public function show(RecurringInvoice $recurringInvoice): View
    {
        return view('recurring-invoices.show', [
            'recurring' => $recurringInvoice->load('customer'),
            'invoices' => $recurringInvoice->invoices()->latest('date')->get(),
            'taxRates' => TaxRate::pluck('name', 'id'),
        ]);
    }

    public function edit(RecurringInvoice $recurringInvoice): View
    {
        return view('recurring-invoices.form', $this->formData($recurringInvoice));
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice, DocumentService $documents): RedirectResponse
    {
        $data = $this->validated($request, $documents);
        if ($recurringInvoice->occurrences === 0) {
            $data['next_date'] = $data['start_date'];
        }
        $recurringInvoice->update($data);

        return redirect()->route('recurring-invoices.show', $recurringInvoice)->with('success', 'Factura recurrente actualizada.');
    }

    public function destroy(RecurringInvoice $recurringInvoice): RedirectResponse
    {
        $recurringInvoice->delete();

        return redirect()->route('recurring-invoices.index')->with('success', 'Plantilla recurrente eliminada. Las facturas ya emitidas se conservan.');
    }

    public function generate(RecurringInvoice $recurringInvoice, RecurringInvoiceService $service): RedirectResponse
    {
        if ($recurringInvoice->status === 'completed') {
            return back()->with('error', 'La plantilla está finalizada.');
        }

        $invoice = $service->generate($recurringInvoice);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Factura '.$invoice->number.' generada desde la plantilla recurrente.');
    }

    protected function validated(Request $request, DocumentService $documents): array
    {
        $companyId = Tenant::id();
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn ($item) => is_array($item) && (! empty($item['product_id']) || trim((string) ($item['description'] ?? '')) !== ''))
                ->values()->all(),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'frequency' => ['required', Rule::in(array_keys(RecurringInvoice::FREQUENCIES))],
            'interval' => ['required', 'integer', 'min:1', 'max:24'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'max_occurrences' => ['nullable', 'integer', 'min:1'],
            'due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'status' => ['required', Rule::in(array_keys(RecurringInvoice::STATUSES))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.tax_rate_id' => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', $companyId)],
        ], ['items.required' => 'Agregue al menos una línea.'], ['title' => 'título', 'customer_id' => 'cliente']);

        $data['items'] = collect($data['items'])->map(fn ($item) => [
            'product_id' => $item['product_id'] ?? null,
            'description' => $item['description'] ?? null,
            'quantity' => (float) $item['quantity'],
            'unit_price' => (float) $item['unit_price'],
            'discount_percent' => (float) ($item['discount_percent'] ?? 0),
            'tax_rate_id' => $item['tax_rate_id'] ?? null,
        ])->all();
        $data['total'] = $documents->calculate($data['items'], 'invoice')['total'];

        return $data;
    }

    protected function formData(RecurringInvoice $recurring): array
    {
        return [
            'recurring' => $recurring,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price_list_id', 'payment_terms', 'tax_id']),
            'products' => Product::where('is_active', true)->orderBy('name')->get()->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'barcode' => $p->barcode, 'unit' => $p->unit,
                'price' => (float) $p->sale_price, 'tax_rate_id' => $p->tax_rate_id, 'stock' => null,
            ]),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('rate')->get(),
            'defaultTaxRateId' => TaxRate::where('is_default', true)->value('id'),
        ];
    }
}
