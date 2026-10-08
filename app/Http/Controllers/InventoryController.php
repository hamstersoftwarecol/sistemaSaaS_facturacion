<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends ModuleController
{
    protected static string $module = 'inventory';

    protected static array $extraActions = [
        'movements' => 'view',
        'adjustForm' => 'create', 'adjust' => 'create',
        'transferForm' => 'create', 'transfer' => 'create',
    ];

    public function index(Request $request): View
    {
        $branches = Branch::orderByDesc('is_main')->orderBy('name')->get();
        $query = Product::with(['stocks', 'category'])->where('type', 'product')->where('track_stock', true);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->orWhere('barcode', 'like', "%{$search}%"));
        }
        if ($category = $request->integer('category_id')) {
            $query->where('category_id', $category);
        }

        $all = $query->orderBy('name')->get();
        if ($request->boolean('low_stock')) {
            $all = $all->filter(fn (Product $p) => $p->stockIn() <= (float) $p->min_stock)->values();
        }

        $valuation = $all->sum(fn (Product $p) => $p->stockIn() * (float) $p->cost_price);
        $saleValue = $all->sum(fn (Product $p) => $p->stockIn() * (float) $p->sale_price);
        $lowCount = $all->filter(fn (Product $p) => $p->stockIn() <= (float) $p->min_stock)->count();

        $page = max(1, $request->integer('page', 1));
        $paginator = new LengthAwarePaginator(
            $all->forPage($page, 25)->values(), $all->count(), 25, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('inventory.index', [
            'products' => $paginator,
            'branches' => $branches,
            'categories' => Category::ofType('product')->get(),
            'valuation' => $valuation,
            'saleValue' => $saleValue,
            'lowCount' => $lowCount,
        ]);
    }

    public function movements(Request $request): View
    {
        $query = StockMovement::with(['product', 'branch', 'user', 'document']);

        if ($branchId = Tenant::branchId()) {
            $query->where('branch_id', $branchId);
        }
        if ($productId = $request->integer('product_id')) {
            $query->where('product_id', $productId);
        }
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('date', '<=', $to);
        }

        return view('inventory.movements', [
            'movements' => $query->latest('date')->latest('id')->paginate(25)->withQueryString(),
            'products' => Product::where('type', 'product')->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function adjustForm(): View
    {
        return view('inventory.adjust', [
            'branches' => Branch::where('is_active', true)->orderByDesc('is_main')->get(),
            'products' => Product::with('stocks')->where('type', 'product')->where('track_stock', true)->orderBy('name')->get(),
            'branchId' => Tenant::workingBranchId(),
        ]);
    }

    public function adjust(Request $request, InventoryService $inventory): RedirectResponse
    {
        $companyId = Tenant::id();
        $data = $request->validate([
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'mode' => ['required', Rule::in(['set', 'delta'])],
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'lines.*.quantity' => ['required', 'numeric'],
        ], [], ['lines' => 'líneas', 'lines.*.product_id' => 'producto', 'lines.*.quantity' => 'cantidad']);

        DB::transaction(function () use ($data, $inventory) {
            foreach ($data['lines'] as $line) {
                $product = Product::find($line['product_id']);
                if ($data['mode'] === 'set') {
                    $inventory->setQuantity($product, (int) $data['branch_id'], (float) $line['quantity'], $data['notes'] ?? null);
                } else {
                    $inventory->move($product, (int) $data['branch_id'], (float) $line['quantity'], 'adjustment', ['notes' => $data['notes'] ?? null]);
                }
            }
        });

        return redirect()->route('inventory.movements')->with('success', 'Ajuste de inventario registrado.');
    }

    public function transferForm(): View
    {
        return view('inventory.transfer', [
            'branches' => Branch::where('is_active', true)->orderByDesc('is_main')->get(),
            'products' => Product::with('stocks')->where('type', 'product')->where('track_stock', true)->orderBy('name')->get(),
        ]);
    }

    public function transfer(Request $request, InventoryService $inventory): RedirectResponse
    {
        $companyId = Tenant::id();
        $data = $request->validate([
            'from_branch_id' => ['required', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'to_branch_id' => ['required', 'different:from_branch_id', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'notes' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ], ['to_branch_id.different' => 'La sucursal de destino debe ser distinta a la de origen.'], [
            'from_branch_id' => 'sucursal de origen', 'to_branch_id' => 'sucursal de destino',
        ]);

        DB::transaction(function () use ($data, $inventory) {
            foreach ($data['lines'] as $line) {
                $inventory->transfer(Product::find($line['product_id']), (int) $data['from_branch_id'], (int) $data['to_branch_id'], (float) $line['quantity'], $data['notes'] ?? null);
            }
        });

        return redirect()->route('inventory.movements')->with('success', 'Transferencia entre sucursales registrada.');
    }
}
