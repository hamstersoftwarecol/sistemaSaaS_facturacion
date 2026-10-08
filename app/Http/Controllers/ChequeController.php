<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Supplier;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ChequeController extends ModuleController
{
    protected static string $module = 'cheques';

    protected static array $extraActions = ['status' => 'edit'];

    public function index(Request $request): View
    {
        $query = Cheque::forCurrentBranch()->with(['customer', 'supplier', 'account']);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('cheque_number', 'like', "%{$search}%")->orWhere('bank_name', 'like', "%{$search}%")->orWhere('party_name', 'like', "%{$search}%"));
        }

        $pending = Cheque::forCurrentBranch()->whereIn('status', ['pending', 'deposited']);

        return view('cheques.index', [
            'cheques' => $query->orderBy('due_date')->paginate(15)->withQueryString(),
            'pendingReceived' => (clone $pending)->where('type', 'received')->sum('amount'),
            'pendingIssued' => (clone $pending)->where('type', 'issued')->sum('amount'),
            'dueSoon' => (clone $pending)->whereDate('due_date', '<=', today()->addDays(7))->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('cheques.form', $this->formData(new Cheque([
            'type' => $request->input('type', 'received'), 'issue_date' => today(), 'due_date' => today()->addDays(30), 'status' => 'pending',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        Cheque::create($this->validated($request));

        return redirect()->route('cheques.index')->with('success', 'Cheque registrado.');
    }

    public function edit(Cheque $cheque): View
    {
        return view('cheques.form', $this->formData($cheque));
    }

    public function update(Request $request, Cheque $cheque): RedirectResponse
    {
        $cheque->update($this->validated($request));

        return redirect()->route('cheques.index')->with('success', 'Cheque actualizado.');
    }

    public function destroy(Cheque $cheque): RedirectResponse
    {
        $cheque->delete();

        return redirect()->route('cheques.index')->with('success', 'Cheque eliminado.');
    }

    public function status(Request $request, Cheque $cheque): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Cheque::STATUSES))],
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('company_id', Tenant::id())],
        ]);

        if ($data['status'] === 'cleared' && ! ($data['account_id'] ?? $cheque->account_id)) {
            return back()->with('error', 'Seleccione la cuenta bancaria donde se compensa el cheque.');
        }

        $cheque->update([
            'status' => $data['status'],
            'account_id' => $data['account_id'] ?? $cheque->account_id,
            'cleared_at' => $data['status'] === 'cleared' ? now() : null,
        ]);

        return back()->with('success', 'Cheque #'.$cheque->cheque_number.': '.$cheque->statusLabel().'.');
    }

    protected function validated(Request $request): array
    {
        $companyId = Tenant::id();

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Cheque::TYPES))],
            'cheque_number' => ['required', 'string', 'max:40'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'party_name' => ['nullable', 'string', 'max:150'],
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(Cheque::STATUSES))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['cheque_number' => 'número de cheque', 'amount' => 'importe', 'due_date' => 'fecha de cobro']);

        if ($data['type'] === 'received') {
            $data['supplier_id'] = null;
        } else {
            $data['customer_id'] = null;
        }
        $data['cleared_at'] = $data['status'] === 'cleared' ? now() : null;

        return $data;
    }

    protected function formData(Cheque $cheque): array
    {
        return [
            'cheque' => $cheque,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'accounts' => Account::active()->get(),
        ];
    }
}
