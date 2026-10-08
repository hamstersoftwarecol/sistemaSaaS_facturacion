<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Reconciliation;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReconciliationController extends ModuleController
{
    protected static string $module = 'reconciliations';

    public function index(): View
    {
        return view('reconciliations.index', [
            'reconciliations' => Reconciliation::with(['account', 'user'])->latest('statement_date')->latest('id')->paginate(15),
            'accounts' => Account::active()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $accounts = Account::active()->get();
        $account = $request->integer('account_id') ? Account::find($request->integer('account_id')) : null;
        $statementDate = $request->input('statement_date', today()->toDateString());

        $transactions = $account
            ? $account->transactions()->whereNull('reconciled_at')->whereDate('date', '<=', $statementDate)->orderBy('date')->orderBy('id')->get()
            : collect();

        return view('reconciliations.create', [
            'accounts' => $accounts,
            'account' => $account,
            'statementDate' => $statementDate,
            'statementBalance' => $request->input('statement_balance'),
            'transactions' => $transactions,
            'openingBalance' => $account?->reconciledBalance() ?? 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = Tenant::id();
        $data = $request->validate([
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'statement_date' => ['required', 'date'],
            'statement_balance' => ['required', 'numeric'],
            'transactions' => ['nullable', 'array'],
            'transactions.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['statement_balance' => 'saldo del extracto']);

        $account = Account::findOrFail($data['account_id']);

        $reconciliation = DB::transaction(function () use ($data, $account) {
            $selected = AccountTransaction::where('account_id', $account->id)->whereNull('reconciled_at')
                ->whereIn('id', $data['transactions'] ?? [])->get();

            $opening = $account->reconciledBalance();
            $cleared = round($opening + $selected->sum(fn ($t) => $t->signedAmount()), 2);

            $reconciliation = Reconciliation::create([
                'account_id' => $account->id,
                'user_id' => auth()->id(),
                'statement_date' => $data['statement_date'],
                'statement_balance' => $data['statement_balance'],
                'opening_balance' => $opening,
                'cleared_balance' => $cleared,
                'difference' => round((float) $data['statement_balance'] - $cleared, 2),
                'transactions_count' => $selected->count(),
                'notes' => $data['notes'] ?? null,
            ]);

            AccountTransaction::whereIn('id', $selected->pluck('id'))->update([
                'reconciliation_id' => $reconciliation->id,
                'reconciled_at' => now(),
            ]);

            return $reconciliation;
        });

        $message = abs((float) $reconciliation->difference) < 0.01
            ? 'Conciliación completada: el saldo coincide con el extracto.'
            : 'Conciliación guardada con una diferencia de '.money($reconciliation->difference).'.';

        return redirect()->route('reconciliations.show', $reconciliation)->with(abs((float) $reconciliation->difference) < 0.01 ? 'success' : 'warning', $message);
    }

    public function show(Reconciliation $reconciliation): View
    {
        return view('reconciliations.show', [
            'reconciliation' => $reconciliation->load(['account', 'user']),
            'transactions' => $reconciliation->transactions()->orderBy('date')->get(),
        ]);
    }

    public function destroy(Reconciliation $reconciliation): RedirectResponse
    {
        DB::transaction(function () use ($reconciliation) {
            AccountTransaction::where('reconciliation_id', $reconciliation->id)->update(['reconciliation_id' => null, 'reconciled_at' => null]);
            $reconciliation->delete();
        });

        return redirect()->route('reconciliations.index')->with('success', 'Conciliación deshecha: los movimientos vuelven a estar pendientes.');
    }
}
