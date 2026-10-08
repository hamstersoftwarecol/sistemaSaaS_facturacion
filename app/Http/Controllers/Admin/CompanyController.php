<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\CompanyProvisioner;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::with(['subscription.plan', 'owner'])->withCount('users');

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('tax_id', 'like', "%{$search}%"));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('admin.companies.index', ['companies' => $query->latest()->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('admin.companies.create', ['plans' => Plan::active()->get()]);
    }

    public function store(Request $request, CompanyProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'currency' => ['nullable', 'string', 'size:3'],
            'currency_symbol' => ['nullable', 'string', 'max:8'],
        ], [], ['company_name' => 'empresa', 'name' => 'nombre del propietario']);

        $user = $provisioner->provision($data, Plan::find($data['plan_id']), $data['billing_cycle']);

        return redirect()->route('admin.companies.show', $user->company_id)->with('success', 'Empresa creada con su configuración inicial.');
    }

    public function show(Company $company, SubscriptionService $service): View
    {
        return view('admin.companies.show', [
            'company' => $company->load(['subscription.plan', 'owner', 'branches']),
            'users' => User::where('company_id', $company->id)->with('role')->get(),
            'usage' => $service->usage($company),
            'payments' => SubscriptionPayment::withoutGlobalScopes()->where('company_id', $company->id)->with(['plan', 'paymentMethod'])->latest()->get(),
            'plans' => Plan::active()->get(),
        ]);
    }

    public function edit(Company $company): View
    {
        return view('admin.companies.edit', ['company' => $company]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ], [], ['name' => 'nombre']);

        $company->update($data);

        return redirect()->route('admin.companies.show', $company)->with('success', 'Empresa actualizada.');
    }

    public function toggle(Company $company): RedirectResponse
    {
        $company->update(['status' => $company->status === 'active' ? 'suspended' : 'active']);

        return back()->with('success', $company->status === 'active' ? 'Empresa reactivada.' : 'Empresa suspendida: sus usuarios no podrán acceder.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        DB::transaction(function () use ($company) {
            User::where('company_id', $company->id)->delete();
            $company->delete();
        });

        return redirect()->route('admin.companies.index')->with('success', 'Empresa y todos sus datos eliminados.');
    }
}
