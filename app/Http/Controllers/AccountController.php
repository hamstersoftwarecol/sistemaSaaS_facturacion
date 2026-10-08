<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends ModuleController
{
    protected static string $module = 'accounts';

    protected static array $extraActions = [
        'transferForm' => 'create', 'transfer' => 'create',
        'storeTransaction' => 'create', 'destroyTransaction' => 'delete',
    ];

    public function index(): View
    {
        $accounts = Account::with('branch')->orderBy('type')->orderBy('name')->get();

        return view('accounts.index', [
            'accounts' => $accounts,
            'totalCash' => $accounts->where('type', 'cash')->sum(fn ($a) => $a->balance()),
            'totalBank' => $accounts->where('type', 'bank')->sum(fn ($a) => $a->balance()),
        ]);
    }

    public function create(): View
    {
        return view('accounts.form', ['account' => new Account(['type' => 'bank', 'is_active' => true]), 'branches' => Branch::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = Account::create($this->validated($request));

        return redirect()->route('accounts.show', $account)->with('success', 'Cuenta creada.');
    }

    public function show(Request $request, Account $account): View
    {
        $query = $account->transactions()->with('source');
        if ($from = $request->input('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('date', '<=', $to);
        }

        return view('accounts.show', [
            'account' => $account,
            'transactions' => $query->latest('date')->latest('id')->paginate(25)->withQueryString(),
            'balance' => $account->balance(),
            'reconciled' => $account->reconciledBalance(),
            'monthIn' => (float) $account->transactions()->where('direction', 'in')->whereDate('date', '>=', now()->startOfMonth())->sum('amount'),
            'monthOut' => (float) $account->transactions()->where('direction', 'out')->whereDate('date', '>=', now()->startOfMonth())->sum('amount'),
        ]);
    }

    public function edit(Account $account): View
    {
        return view('accounts.form', ['account' => $account, 'branches' => Branch::orderBy('name')->get()]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $account->update($this->validated($request));

        return redirect()->route('accounts.show', $account)->with('success', 'Cuenta actualizada.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->transactions()->exists()) {
            return back()->with('error', 'La cuenta tiene movimientos. Desactívela en lugar de eliminarla.');
        }
        $account->delete();

        return redirect()->route('accounts.index')->with('success', 'Cuenta eliminada.');
    }

    /**
     * Depósito o retiro manual.
     */
    public function storeTransaction(Request $request, Account $account): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['deposit', 'withdrawal'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ], [], ['amount' => 'importe', 'description' => 'descripción']);

        $account->transactions()->create($data + [
            'direction' => $data['type'] === 'deposit' ? 'in' : 'out',
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', $data['type'] === 'deposit' ? 'Depósito registrado.' : 'Retiro registrado.');
    }

    public function destroyTransaction(AccountTransaction $transaction): RedirectResponse
    {
        if ($transaction->source_type) {
            return back()->with('error', 'Este movimiento proviene de otro registro (pago, gasto, etc.). Elimínelo desde su módulo.');
        }
        if ($transaction->reconciled_at) {
            return back()->with('error', 'No se puede eliminar un movimiento conciliado.');
        }

        DB::transaction(function () use ($transaction) {
            if ($transaction->type === 'transfer' && $transaction->reference) {
                AccountTransaction::where('type', 'transfer')->where('reference', $transaction->reference)->whereNull('reconciled_at')->delete();
            } else {
                $transaction->delete();
            }
        });

        return back()->with('success', 'Movimiento eliminado.');
    }

    public function transferForm(): View
    {
        return view('accounts.transfer', ['accounts' => Account::active()->get()]);
    }

    public function transfer(Request $request): RedirectResponse
    {
        $companyId = Tenant::id();
        $data = $request->validate([
            'from_account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'to_account_id' => ['required', 'different:from_account_id', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ], ['to_account_id.different' => 'La cuenta destino debe ser distinta a la de origen.'], [
            'from_account_id' => 'cuenta de origen', 'to_account_id' => 'cuenta de destino', 'amount' => 'importe',
        ]);

        DB::transaction(function () use ($data) {
            $from = Account::find($data['from_account_id']);
            $to = Account::find($data['to_account_id']);
            $reference = 'TRF-'.Str::upper(Str::random(8));
            $description = $data['description'] ?: "Transferencia {$from->name} → {$to->name}";

            foreach ([[$from, 'out'], [$to, 'in']] as [$account, $direction]) {
                $account->transactions()->create([
                    'type' => 'transfer', 'direction' => $direction, 'amount' => $data['amount'], 'date' => $data['date'],
                    'description' => $description, 'reference' => $reference, 'user_id' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('accounts.index')->with('success', 'Transferencia registrada.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', Tenant::id())],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_number' => ['nullable', 'string', 'max:60'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ], [], ['name' => 'nombre', 'opening_balance' => 'saldo inicial']);

        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
