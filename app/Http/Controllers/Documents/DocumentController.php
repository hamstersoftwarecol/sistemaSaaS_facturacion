<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\ModuleController;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Document;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Services\DocumentService;
use App\Services\SubscriptionService;
use App\Support\Codes;
use App\Support\Pdf;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Lógica común de todos los documentos comerciales. Cada tipo de documento
 * tiene un controlador hijo que solo define $type y $module.
 */
abstract class DocumentController extends ModuleController
{
    protected string $type;

    protected static array $extraActions = [
        'print' => 'view', 'pdf' => 'view', 'calculate' => 'view',
        'convert' => 'view', 'status' => 'edit', 'duplicate' => 'create',
    ];

    public function __construct(protected DocumentService $service) {}

    protected function config(?string $key = null)
    {
        return Document::config($this->type, $key);
    }

    protected function ensureType(Document $document): void
    {
        abort_unless($document->type === $this->type, 404);
    }

    public function index(Request $request): View
    {
        $party = $this->config('party');
        $query = Document::ofType($this->type)->forCurrentBranch()->with([$party, 'branch']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($q) use ($search, $party) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas($party, fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'overdue') {
                $query->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today());
            } else {
                $query->where('status', $status);
            }
        }

        if ($from = $request->input('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('date', '<=', $to);
        }

        $summary = (clone $query)->selectRaw('COUNT(*) as count, COALESCE(SUM(total),0) as total, COALESCE(SUM(balance),0) as balance')->first();

        return view('documents.index', [
            'type' => $this->type,
            'config' => $this->config(),
            'documents' => $query->latest('date')->latest('id')->paginate(15)->withQueryString(),
            'summary' => $summary,
        ]);
    }

    public function create(Request $request): View
    {
        $company = Tenant::company();
        $document = new Document([
            'type' => $this->type,
            'date' => today(),
            'due_date' => $this->type === 'invoice' ? today()->addDays(30) : ($this->type === 'quote' ? today()->addDays(15) : null),
            'notes' => $this->config('party') === 'customer' ? $company->invoice_notes : null,
            'terms' => in_array($this->type, ['invoice', 'quote']) ? $company->invoice_terms : null,
            'affects_stock' => true,
            'customer_id' => $request->integer('customer_id') ?: null,
            'supplier_id' => $request->integer('supplier_id') ?: null,
        ]);
        $document->status = $this->config('default_status');

        $items = [];
        if ($parentId = $request->integer('parent_id')) {
            $parent = Document::with('items')->find($parentId);
            if ($parent) {
                $document->parent_id = $parent->id;
                $document->customer_id = $parent->customer_id;
                $document->supplier_id = $parent->supplier_id;
                $items = $parent->items->map(fn ($i) => $i->only(['product_id', 'description', 'quantity', 'unit', 'unit_price', 'discount_percent', 'tax_rate_id']))->all();
            }
        }

        return view('documents.form', $this->formData($document, $items));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->type === 'invoice' && ! app(SubscriptionService::class)->canCreate(Tenant::company(), 'invoices')) {
            return back()->withInput()->with('error', app(SubscriptionService::class)->limitMessage('invoices'));
        }

        $document = $this->service->save($this->type, $this->validated($request));

        return redirect()->route($document->routeName('show'), $document)
            ->with('success', $this->config('label').' '.$document->number.' '.Document::phrase($this->type, 'created').' correctamente.');
    }

    public function show(Document $document): View
    {
        $this->ensureType($document);
        $document->load(['items.product', 'customer', 'supplier', 'parent', 'children', 'payments.account', 'user', 'branch']);

        return view('documents.show', [
            'document' => $document,
            'config' => $this->config(),
            'conversions' => DocumentService::CONVERSIONS[$this->type] ?? [],
            'customFields' => CustomField::for('document'),
            'qr' => Codes::qrSvg($document->verificationUrl(), 110),
        ]);
    }

    public function edit(Document $document): View
    {
        $this->ensureType($document);
        $items = $document->items->map(fn ($i) => $i->only(['product_id', 'description', 'quantity', 'unit', 'unit_price', 'discount_percent', 'tax_rate_id']))->all();

        return view('documents.form', $this->formData($document, $items));
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $this->ensureType($document);
        $this->service->save($this->type, $this->validated($request, $document), $document);

        return redirect()->route($document->routeName('show'), $document)
            ->with('success', $this->config('label').' '.Document::phrase($this->type, 'updated').' correctamente.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->ensureType($document);
        $this->service->delete($document);

        return redirect()->route($this->config('route').'.index')
            ->with('success', $this->config('label').' '.$document->number.' '.Document::phrase($this->type, 'deleted').'.');
    }

    public function calculate(Request $request): JsonResponse
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($item) => is_array($item) && (! empty($item['product_id']) || trim((string) ($item['description'] ?? '')) !== ''))
            ->values()->all();

        $result = $this->service->calculate($items, $this->type);
        unset($result['items']);

        return response()->json($result);
    }

    public function print(Request $request, Document $document): View
    {
        $this->ensureType($document);

        return view('documents.print', $this->templateData($document, $request->input('style')));
    }

    public function pdf(Request $request, Document $document)
    {
        $this->ensureType($document);
        $data = $this->templateData($document, $request->input('style'));

        return Pdf::response('documents.templates.'.$data['style'], $data, $document->number.'.pdf', $request->boolean('download'));
    }

    public function convert(Document $document, string $target): RedirectResponse
    {
        $this->ensureType($document);
        abort_unless(isset(Document::TYPES[$target]), 404);
        Gate::authorize(Document::config($target, 'module').'.create');

        $new = $this->service->convert($document->load('items'), $target);

        return redirect()->route($new->routeName('show'), $new)
            ->with('success', Document::config($target, 'label').' '.$new->number.' '.Document::phrase($target, 'generated').' desde '.$document->number.'.');
    }

    public function status(Request $request, Document $document): RedirectResponse
    {
        $this->ensureType($document);
        $request->validate(['status' => ['required', Rule::in(array_keys($this->config('statuses')))]]);
        $this->service->changeStatus($document, $request->input('status'));

        return back()->with('success', 'Estado actualizado a "'.$document->fresh()->statusLabel().'".');
    }

    public function duplicate(Document $document): RedirectResponse
    {
        $this->ensureType($document);
        $copy = $this->service->duplicate($document->load('items'));

        return redirect()->route($copy->routeName('edit'), $copy)
            ->with('success', 'Documento duplicado como '.$copy->number.'. Revise los datos antes de enviarlo.');
    }

    protected function validated(Request $request, ?Document $document = null): array
    {
        $companyId = Tenant::id();
        $party = $this->config('party');

        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn ($item) => is_array($item) && (! empty($item['product_id']) || trim((string) ($item['description'] ?? '')) !== ''))
                ->values()->all(),
        ]);

        $exists = fn (string $table) => Rule::exists($table, 'id')->where('company_id', $companyId);

        $data = $request->validate([
            "{$party}_id" => ['required', $exists($party === 'customer' ? 'customers' : 'suppliers')],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', $this->parentType()
                ? Rule::exists('documents', 'id')->where('company_id', $companyId)->where('type', $this->parentType())
                : 'prohibited'],
            'price_list_id' => ['nullable', $exists('price_lists')],
            'status' => ['nullable', Rule::in(array_keys($this->config('statuses')))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'affects_stock' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', $exists('products')],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.tax_rate_id' => ['nullable', $exists('tax_rates')],
        ] + CustomField::rulesFor('document'), [
            'items.required' => 'Agregue al menos una línea al documento.',
            'items.min' => 'Agregue al menos una línea al documento.',
        ], [
            'customer_id' => 'cliente',
            'supplier_id' => 'proveedor',
            'items.*.quantity' => 'cantidad',
            'items.*.unit_price' => 'precio',
        ] + CustomField::attributesFor('document'));

        $data['affects_stock'] = $request->boolean('affects_stock', true);
        $data['custom_fields'] = CustomField::valuesFrom('document', $request);

        if (! in_array($this->type, ['quote', 'delivery_note', 'purchase_order'])) {
            unset($data['status']);
        }

        return $data;
    }

    protected function formData(Document $document, array $items): array
    {
        $party = $this->config('party');

        $parties = $party === 'customer'
            ? Customer::where('is_active', true)->orWhere('id', $document->customer_id)->orderBy('name')->get(['id', 'name', 'tax_id', 'price_list_id', 'payment_terms'])
            : Supplier::where('is_active', true)->orWhere('id', $document->supplier_id)->orderBy('name')->get(['id', 'name', 'tax_id', 'payment_terms']);

        $products = Product::with(['stocks'])->where('is_active', true)->orderBy('name')->limit(2000)->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'unit' => $p->unit,
                'price' => (float) ($party === 'supplier' ? $p->cost_price : $p->sale_price),
                'tax_rate_id' => $p->tax_rate_id,
                'stock' => $p->track_stock && $p->type === 'product' ? $p->currentStock() : null,
            ]);

        $priceLists = PriceList::with('items')->where('is_active', true)->get()->mapWithKeys(fn ($list) => [
            $list->id => [
                'name' => $list->name,
                'percentage' => (float) $list->percentage,
                'prices' => $list->items->mapWithKeys(fn ($i) => [$i->product_id => (float) $i->price]),
            ],
        ]);

        $parentType = $this->parentType();
        $parents = $parentType
            ? Document::ofType($parentType)->where('status', '!=', 'cancelled')->latest('id')->limit(500)
                ->get(['id', 'number', 'customer_id', 'supplier_id', 'total'])
            : collect();

        return [
            'type' => $this->type,
            'config' => $this->config(),
            'document' => $document,
            'items' => $items,
            'parties' => $parties,
            'products' => $products,
            'taxRates' => TaxRate::where('is_active', true)->orderBy('rate')->get(),
            'defaultTaxRateId' => TaxRate::where('is_default', true)->value('id'),
            'priceLists' => $priceLists,
            'parents' => $parents,
            'parentType' => $parentType,
            'customFields' => CustomField::for('document'),
        ];
    }

    /**
     * Tipo de documento que puede figurar como origen en el formulario.
     */
    protected function parentType(): ?string
    {
        return match ($this->type) {
            'credit_note', 'delivery_note' => 'invoice',
            'debit_note' => 'purchase_order',
            default => null,
        };
    }

    protected function templateData(Document $document, ?string $style = null): array
    {
        $company = Tenant::company();
        $style = array_key_exists((string) $style, Company::INVOICE_STYLES) ? $style : $company->invoice_style;
        $document->load(['items', 'customer', 'supplier', 'parent', 'branch']);

        return [
            'document' => $document,
            'company' => $company,
            'style' => $style,
            'color' => $company->primary_color ?: '#4f46e5',
            'logo' => $company->logoDataUri(),
            'qr' => Codes::qrDataUri($document->verificationUrl()),
            'customFields' => CustomField::for('document')->where('show_on_document', true),
            'partyFields' => CustomField::for($document->cfg('party'))->where('show_on_document', true),
        ];
    }
}
