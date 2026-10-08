<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PriceListController extends ModuleController
{
    protected static string $module = 'price_lists';

    public function index(): View
    {
        return view('price-lists.index', [
            'priceLists' => PriceList::withCount(['items', 'customers'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('price-lists.form', $this->formData(new PriceList(['type' => 'percentage', 'percentage' => 0, 'is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $list = DB::transaction(function () use ($request) {
            $list = PriceList::create($this->validated($request));
            $this->syncItems($list, $request);

            return $list;
        });

        return redirect()->route('price-lists.show', $list)->with('success', 'Lista de precios creada.');
    }

    public function show(PriceList $priceList): View
    {
        $priceList->load('items');
        $products = Product::where('is_active', true)->orderBy('name')->paginate(25);

        return view('price-lists.show', compact('priceList', 'products'));
    }

    public function edit(PriceList $priceList): View
    {
        return view('price-lists.form', $this->formData($priceList->load('items')));
    }

    public function update(Request $request, PriceList $priceList): RedirectResponse
    {
        DB::transaction(function () use ($request, $priceList) {
            $priceList->update($this->validated($request));
            $this->syncItems($priceList, $request);
        });

        return redirect()->route('price-lists.show', $priceList)->with('success', 'Lista de precios actualizada.');
    }

    public function destroy(PriceList $priceList): RedirectResponse
    {
        $priceList->delete();

        return redirect()->route('price-lists.index')->with('success', 'Lista de precios eliminada.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(PriceList::TYPES))],
            'percentage' => ['nullable', 'numeric', 'between:-100,1000'],
            'is_active' => ['nullable', 'boolean'],
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'numeric', 'min:0'],
        ], [], ['name' => 'nombre', 'percentage' => 'porcentaje']);

        unset($data['prices']);
        $data['percentage'] = $data['percentage'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function syncItems(PriceList $list, Request $request): void
    {
        $productIds = Product::pluck('id')->all();

        foreach ((array) $request->input('prices', []) as $productId => $price) {
            if (! in_array((int) $productId, $productIds, true)) {
                continue;
            }
            if ($price === null || $price === '') {
                $list->items()->where('product_id', $productId)->delete();
            } else {
                $list->items()->updateOrCreate(['product_id' => $productId], ['price' => $price]);
            }
        }
    }

    protected function formData(PriceList $priceList): array
    {
        return [
            'priceList' => $priceList,
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'sale_price']),
            'prices' => $priceList->exists ? $priceList->items->pluck('price', 'product_id') : collect(),
        ];
    }
}
