<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Income;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncomeController extends ModuleController
{
    protected static string $module = 'incomes';

    public function index(Request $request): View
    {
        $query = Income::forCurrentBranch()->with(['category', 'customer', 'account']);

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

        return view('incomes.index', [
            'incomes' => (clone $query)->latest('date')->latest('id')->paginate(15)->withQueryString(),
            'total' => $query->sum('amount'),
            'categories' => Category::ofType('income')->get(),
        ]);
    }

    public function create(): View
    {
        return view('incomes.form', $this->formData(new Income(['date' => today()])));
    }

    public function store(Request $request): RedirectResponse
    {
        Income::create($this->validated($request));

        return redirect()->route('incomes.index')->with('success', 'Ingreso registrado.');
    }

    public function edit(Income $income): View
    {
        return view('incomes.form', $this->formData($income));
    }

    public function update(Request $request, Income $income): RedirectResponse
    {
        $income->update($this->validated($request));

        return redirect()->route('incomes.index')->with('success', 'Ingreso actualizado.');
    }

    public function destroy(Income $income): RedirectResponse
    {
        $income->delete();

        return redirect()->route('incomes.index')->with('success', 'Ingreso eliminado.');
    }

    protected function validated(Request $request): array
    {
        $companyId = Tenant::id();

        return $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('company_id', $companyId)->where('type', 'income')],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'reference' => ['nullable', 'string', 'max:100'],
        ], [], ['description' => 'descripción', 'amount' => 'importe', 'account_id' => 'cuenta']);
    }

    protected function formData(Income $income): array
    {
        return [
            'income' => $income,
            'categories' => Category::ofType('income')->get(),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'accounts' => Account::active()->get(),
        ];
    }
}
