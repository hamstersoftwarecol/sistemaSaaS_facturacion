<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = SubscriptionPayment::with(['company', 'plan', 'paymentMethod']);
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return view('admin.payments.index', [
            'payments' => $query->latest()->paginate(20)->withQueryString(),
            'approvedTotal' => SubscriptionPayment::where('status', 'approved')->sum('amount'),
            'pendingTotal' => SubscriptionPayment::where('status', 'pending')->sum('amount'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.payments.create', [
            'companies' => Company::orderBy('name')->get(),
            'plans' => Plan::orderBy('sort_order')->get(),
            'methods' => PaymentMethod::orderBy('sort_order')->get(),
            'companyId' => $request->integer('company_id') ?: null,
        ]);
    }

    public function store(Request $request, SubscriptionService $service): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['company_id' => 'empresa', 'plan_id' => 'plan', 'amount' => 'importe']);

        $payment = SubscriptionPayment::create($data + ['status' => 'pending', 'paid_at' => now()]);
        $service->approvePayment($payment, $request->user());

        return redirect()->route('admin.payments.index')->with('success', 'Pago registrado y suscripción activada.');
    }

    public function approve(Request $request, SubscriptionPayment $payment, SubscriptionService $service): RedirectResponse
    {
        abort_unless($payment->status === 'pending', 422, 'El pago ya fue procesado.');
        $service->approvePayment($payment, $request->user());

        return back()->with('success', 'Pago aprobado: la suscripción de '.$payment->company->name.' está activa.');
    }

    public function reject(SubscriptionPayment $payment): RedirectResponse
    {
        abort_unless($payment->status === 'pending', 422, 'El pago ya fue procesado.');
        $payment->update(['status' => 'rejected']);

        return back()->with('success', 'Pago rechazado.');
    }
}
