<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Services\LoanService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoanController extends ModuleController
{
    protected static string $module = 'loans';

    protected static array $extraActions = ['payInstallment' => 'edit', 'unpayInstallment' => 'edit'];

    public function index(): View
    {
        $loans = Loan::with(['installments', 'account'])->latest('start_date')->get();

        return view('loans.index', [
            'loans' => $loans,
            'borrowedOutstanding' => $loans->where('type', 'borrowed')->where('status', 'active')->sum(fn ($l) => $l->outstanding()),
            'lentOutstanding' => $loans->where('type', 'lent')->where('status', 'active')->sum(fn ($l) => $l->outstanding()),
            'upcoming' => LoanInstallment::with('loan')->whereHas('loan', fn ($q) => $q->where('status', 'active'))
                ->where('status', '!=', 'paid')->orderBy('due_date')->limit(8)->get(),
        ]);
    }

    public function create(): View
    {
        return view('loans.form', [
            'loan' => new Loan(['type' => 'borrowed', 'frequency' => 'monthly', 'start_date' => today(), 'installments_count' => 12, 'interest_rate' => 12]),
            'accounts' => Account::active()->get(),
        ]);
    }

    public function store(Request $request, LoanService $service): RedirectResponse
    {
        $loan = DB::transaction(function () use ($request, $service) {
            $loan = Loan::create($this->validated($request) + ['status' => 'active']);
            $service->regenerate($loan);

            return $loan;
        });

        return redirect()->route('loans.show', $loan)->with('success', 'Préstamo registrado y cuadro de amortización generado.');
    }

    public function show(Loan $loan): View
    {
        return view('loans.show', ['loan' => $loan->load(['installments', 'account']), 'accounts' => Account::active()->get()]);
    }

    public function edit(Loan $loan): View
    {
        return view('loans.form', ['loan' => $loan, 'accounts' => Account::active()->get()]);
    }

    public function update(Request $request, Loan $loan, LoanService $service): RedirectResponse
    {
        $data = $this->validated($request);
        $hasPaid = $loan->installments()->where('status', 'paid')->exists();

        DB::transaction(function () use ($loan, $data, $hasPaid, $service, $request) {
            if ($hasPaid) {
                // Con cuotas pagadas solo se permiten cambios descriptivos.
                $loan->update(collect($data)->only(['party_name', 'reference', 'notes', 'account_id'])->all() + ['status' => $request->input('status', $loan->status)]);
            } else {
                $loan->update($data + ['status' => $request->input('status', $loan->status)]);
                $service->regenerate($loan);
            }
        });

        return redirect()->route('loans.show', $loan)->with('success', $hasPaid
            ? 'Préstamo actualizado (el cuadro de amortización no se modificó porque hay cuotas pagadas).'
            : 'Préstamo actualizado y cuadro recalculado.');
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        DB::transaction(function () use ($loan) {
            $loan->installments()->get()->each->delete();
            $loan->delete();
        });

        return redirect()->route('loans.index')->with('success', 'Préstamo eliminado.');
    }

    public function payInstallment(Request $request, LoanInstallment $installment): RedirectResponse
    {
        $loan = Loan::findOrFail($installment->loan_id); // aplica el aislamiento por empresa
        $data = $request->validate([
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', Tenant::id())],
            'paid_at' => ['required', 'date'],
        ], [], ['account_id' => 'cuenta']);

        DB::transaction(function () use ($installment, $loan, $data) {
            $installment->update(['status' => 'paid', 'account_id' => $data['account_id'], 'paid_at' => $data['paid_at']]);
            if ($loan->installments()->where('status', '!=', 'paid')->doesntExist()) {
                $loan->update(['status' => 'paid']);
            }
        });

        return back()->with('success', "Cuota {$installment->number} registrada como pagada.");
    }

    public function unpayInstallment(LoanInstallment $installment): RedirectResponse
    {
        $loan = Loan::findOrFail($installment->loan_id);

        DB::transaction(function () use ($installment, $loan) {
            $installment->update(['status' => 'pending', 'paid_at' => null]);
            if ($loan->status === 'paid') {
                $loan->update(['status' => 'active']);
            }
        });

        return back()->with('success', "Pago de la cuota {$installment->number} revertido.");
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(Loan::TYPES))],
            'party_name' => ['required', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:100'],
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('company_id', Tenant::id())],
            'principal' => ['required', 'numeric', 'gt:0'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:200'],
            'installments_count' => ['required', 'integer', 'min:1', 'max:480'],
            'frequency' => ['required', Rule::in(array_keys(Loan::FREQUENCIES))],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'party_name' => 'entidad / persona', 'principal' => 'capital', 'interest_rate' => 'tasa de interés',
            'installments_count' => 'número de cuotas',
        ]);
    }
}
