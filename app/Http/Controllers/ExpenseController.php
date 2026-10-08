<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Supplier;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends ModuleController
{
    protected static string $module = 'expenses';

    public function index(Request $request): View
    {
        $query = Expense::forCurrentBranch()->with(['category', 'supplier', 'account']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('description', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%"));
        }
        if ($category = $request->integer('category_id')) {
            $query->where('category_id', $category);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('date', '<=', $to);
        }

        return view('expenses.index', [
            'expenses' => (clone $query)->latest('date')->latest('id')->paginate(15)->withQueryString(),
            'total' => $query->sum('amount'),
            'categories' => Category::ofType('expense')->get(),
        ]);
    }

    public function create(): View
    {
        return view('expenses.form', $this->formData(new Expense(['date' => today()])));
    }

    public function store(Request $request): RedirectResponse
    {
        Expense::create($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Gasto registrado.');
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.form', $this->formData($expense));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Gasto actualizado.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Gasto eliminado.');
    }

    protected function validated(Request $request): array
    {
        $companyId = Tenant::id();

        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $companyId)->where('type', 'expense')],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'reference' => ['nullable', 'string', 'max:100'],
        ], [], ['description' => 'descripción', 'amount' => 'importe', 'account_id' => 'cuenta']);

        $data['tax_amount'] = $data['tax_amount'] ?? 0;

        return $data;
    }

    protected function formData(Expense $expense): array
    {
        return [
            'expense' => $expense,
            'categories' => Category::ofType('expense')->get(),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'accounts' => Account::active()->get(),
        ];
    }
}
