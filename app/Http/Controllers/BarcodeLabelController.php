<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\Codes;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BarcodeLabelController extends ModuleController
{
    protected static string $module = 'products';

    protected static array $extraActions = ['print' => 'view'];

    public function index(Request $request): View
    {
        $query = Product::with('category')->where('is_active', true);
        if ($category = $request->integer('category_id')) {
            $query->where('category_id', $category);
        }

        return view('labels.index', [
            'products' => $query->orderBy('name')->get(),
            'categories' => Category::ofType('product')->get(),
            'preselected' => array_map('intval', (array) $request->input('products', [])),
        ]);
    }

    public function print(Request $request): View
    {
        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:500'],
            'columns' => ['required', 'integer', Rule::in([2, 3, 4, 5])],
            'show_price' => ['nullable', 'boolean'],
            'show_name' => ['nullable', 'boolean'],
        ]);

        $products = Product::whereIn('id', array_keys(array_filter($data['quantities'])))->get()->keyBy('id');
        $labels = [];

        foreach ($data['quantities'] as $productId => $count) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }
            $svg = Codes::barcodeSvg($product->barcodeValue(), 1.4, 38);
            for ($i = 0; $i < (int) $count; $i++) {
                $labels[] = ['product' => $product, 'svg' => $svg];
            }
        }

        abort_if(empty($labels), 422, 'Seleccione al menos un producto con cantidad mayor que cero.');

        return view('labels.print', [
            'labels' => $labels,
            'columns' => (int) $data['columns'],
            'showPrice' => $request->boolean('show_price'),
            'showName' => $request->boolean('show_name'),
            'company' => Tenant::company(),
        ]);
    }
}
