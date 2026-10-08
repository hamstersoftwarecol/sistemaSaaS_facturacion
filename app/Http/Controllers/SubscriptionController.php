<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        // La página de suscripción es visible para todos los usuarios de la empresa
        // (a ella se redirige cuando el plan vence); cambiar de plan requiere permiso.
        return [new Middleware('can:subscription.edit', only: ['checkout', 'pay'])];
    }

    public function index(SubscriptionService $service): View
    {
        $company = Tenant::company();

        return view('subscription.index', [
            'company' => $company,
            'subscription' => $company->subscription?->load('plan'),
            'plans' => Plan::active()->get(),
            'usage' => $service->usage($company),
            'payments' => SubscriptionPayment::with(['plan', 'paymentMethod'])->latest()->get(),
        ]);
    }

    public function checkout(Request $request, Plan $plan): View
    {
        abort_unless($plan->is_active, 404);

        return view('subscription.checkout', [
            'plan' => $plan,
            'cycle' => $request->input('cycle') === 'yearly' ? 'yearly' : 'monthly',
            'methods' => PaymentMethod::where('is_active', true)->orderBy('sort_order')->get(),
            'company' => Tenant::company(),
        ]);
    }

    public function pay(Request $request, Plan $plan, SubscriptionService $service): RedirectResponse
    {
        abort_unless($plan->is_active, 404);
        $company = Tenant::company();

        $data = $request->validate([
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'payment_method_id' => [$plan->isFree() ? 'nullable' : 'required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'reference' => [$plan->isFree() ? 'nullable' : 'required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [], ['payment_method_id' => 'método de pago', 'reference' => 'referencia del pago']);

        if ($plan->isFree()) {
            $service->activate($company, $plan, $data['billing_cycle']);

            return redirect()->route('subscription.index')->with('success', 'Plan '.$plan->name.' activado.');
        }

        SubscriptionPayment::create([
            'company_id' => $company->id,
            'subscription_id' => $company->subscription?->id,
            'plan_id' => $plan->id,
            'payment_method_id' => $data['payment_method_id'],
            'billing_cycle' => $data['billing_cycle'],
            'amount' => $plan->priceFor($data['billing_cycle']),
            'reference' => $data['reference'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'paid_at' => now(),
        ]);

        return redirect()->route('subscription.index')
            ->with('success', 'Pago registrado. Su plan se activará en cuanto el equipo de la plataforma lo verifique.');
    }
}
