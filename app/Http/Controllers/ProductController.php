<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\DocumentItem;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\TaxRate;
use App\Services\InventoryService;
use App\Services\SubscriptionService;
use App\Support\Codes;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends ModuleController
{
    protected static string $module = 'products';

    protected static array $extraActions = ['lookup' => 'view'];

    public function index(Request $request): View
    {
        $query = Product::with(['category', 'taxRate', 'stocks']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%"));
        }
        if ($category = $request->integer('category_id')) {
            $query->where('category_id', $category);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $products = $query->orderBy('name')->paginate(20)->withQueryString();

        if ($request->boolean('low_stock')) {
            $products->setCollection($products->getCollection()->filter->isLowStock()->values());
        }

        return view('products.index', [
            'products' => $products,
            'categories' => Category::ofType('product')->get(),
        ]);
    }

    public function create(): View
    {
        return view('products.form', $this->formData(new Product([
            'type' => 'product', 'unit' => 'und', 'track_stock' => true, 'is_active' => true,
            'tax_rate_id' => TaxRate::where('is_default', true)->value('id'),
            'sku' => $this->suggestSku(),
        ])));
    }

    public function store(Request $request, InventoryService $inventory, SubscriptionService $subscriptions): RedirectResponse
    {
        if (! $subscriptions->canCreate(Tenant::company(), 'products')) {
            return back()->withInput()->with('error', $subscriptions->limitMessage('products'));
        }

        $data = $this->validated($request);

        $product = DB::transaction(function () use ($data, $request, $inventory) {
            $product = Product::create($data);
            foreach ((array) $request->input('opening_stock', []) as $branchId => $quantity) {
                if ((float) $quantity != 0 && Branch::whereKey($branchId)->exists()) {
                    $inventory->move($product, (int) $branchId, (float) $quantity, 'opening', ['notes' => 'Inventario inicial']);
                }
            }

            return $product;
        });

        return redirect()->route('products.show', $product)->with('success', 'Producto creado correctamente.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'taxRate', 'stocks.branch']);

        return view('products.show', [
            'product' => $product,
            'branches' => Branch::orderByDesc('is_main')->orderBy('name')->get(),
            'movements' => StockMovement::where('product_id', $product->id)->with(['branch', 'user'])->latest('date')->latest('id')->limit(20)->get(),
            'priceLists' => PriceList::with('items')->where('is_active', true)->get(),
            'barcode' => Codes::barcodeSvg($product->barcodeValue()),
            'sold' => (float) DocumentItem::where('product_id', $product->id)->whereHas('document', fn ($q) => $q->where('type', 'invoice')->where('status', '!=', 'cancelled'))->sum('quantity'),
            'customFields' => CustomField::for('product'),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('products.show', $product)->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (DocumentItem::where('product_id', $product->id)->exists()) {
            $product->update(['is_active' => false]);

            return redirect()->route('products.index')->with('warning', 'El producto tiene movimientos en documentos: se ha desactivado en lugar de eliminarse.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado.');
    }

    /**
     * Búsqueda rápida (JSON) usada por la facturación rápida con teclado.
     */
    public function lookup(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q'));
        $customer = $request->integer('customer_id') ? Customer::with('priceList.items')->find($request->integer('customer_id')) : null;

        $query = Product::with(['stocks', 'taxRate'])->where('is_active', true);

        if ($term !== '') {
            $exact = (clone $query)->where(fn ($q) => $q->where('barcode', $term)->orWhere('sku', $term))->first();
            if ($exact) {
                return response()->json(['exact' => true, 'results' => [$this->lookupPayload($exact, $customer)]]);
            }
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "{$term}%"));
        }

        $results = $query->orderBy('name')->limit(12)->get()->map(fn ($p) => $this->lookupPayload($p, $customer));

        return response()->json(['exact' => false, 'results' => $results]);
    }

    protected function lookupPayload(Product $product, ?Customer $customer): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'unit' => $product->unit,
            'price' => $product->priceForCustomer($customer),
            'tax_rate_id' => $product->tax_rate_id,
            'tax_rate' => (float) ($product->taxRate?->rate ?? 0),
            'stock' => $product->track_stock && $product->type === 'product' ? $product->currentStock() : null,
        ];
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $companyId = Tenant::id();

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Product::TYPES))],
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['required', 'string', 'max:60', Rule::unique('products')->where('company_id', $companyId)->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'tax_rate_id' => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', $companyId)],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'opening_stock' => ['nullable', 'array'],
            'opening_stock.*' => ['nullable', 'numeric'],
        ] + CustomField::rulesFor('product'), [], [
            'name' => 'nombre', 'sku' => 'código (SKU)', 'cost_price' => 'precio de costo', 'sale_price' => 'precio de venta',
        ] + CustomField::attributesFor('product'));

        unset($data['opening_stock']);
        $data['track_stock'] = $data['type'] === 'product' && $request->boolean('track_stock');
        $data['is_active'] = $request->boolean('is_active');
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['custom_fields'] = CustomField::valuesFrom('product', $request);

        return $data;
    }

    protected function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::ofType('product')->get(),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('rate')->get(),
            'branches' => Branch::orderByDesc('is_main')->orderBy('name')->get(),
            'customFields' => CustomField::for('product'),
        ];
    }

    protected function suggestSku(): string
    {
        $next = (int) Product::count() + 1;
        do {
            $sku = 'P'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
