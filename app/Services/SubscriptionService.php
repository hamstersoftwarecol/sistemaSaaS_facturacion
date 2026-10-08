<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Inicia la suscripción de una empresa: gratis (activa), o de pago (prueba).
     */
    public function start(Company $company, Plan $plan, string $cycle = 'monthly'): Subscription
    {
        $isFree = $plan->isFree();

        return Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'status' => $isFree ? 'active' : 'trialing',
            'price' => $plan->priceFor($cycle),
            'starts_at' => now(),
            'trial_ends_at' => $isFree ? null : now()->addDays(max(1, $plan->trial_days)),
            'ends_at' => null,
        ]);
    }

    /**
     * Activa (o renueva) un plan para la empresa por un ciclo de facturación.
     */
    public function activate(Company $company, Plan $plan, string $cycle = 'monthly'): Subscription
    {
        $current = $company->subscription;
        $from = ($current && $current->plan_id === $plan->id && $current->status === 'active' && $current->ends_at?->isFuture())
            ? $current->ends_at
            : now();

        $endsAt = $plan->isFree() ? null : ($cycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth());

        if ($current) {
            $current->update([
                'plan_id' => $plan->id,
                'billing_cycle' => $cycle,
                'status' => 'active',
                'price' => $plan->priceFor($cycle),
                'starts_at' => $current->starts_at ?? now(),
                'trial_ends_at' => null,
                'ends_at' => $endsAt,
                'cancelled_at' => null,
            ]);

            return $current;
        }

        return Subscription::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'status' => 'active',
            'price' => $plan->priceFor($cycle),
            'starts_at' => now(),
            'ends_at' => $endsAt,
        ]);
    }

    public function approvePayment(SubscriptionPayment $payment, ?User $approver = null): void
    {
        DB::transaction(function () use ($payment, $approver) {
            $company = Company::findOrFail($payment->company_id);
            $subscription = $this->activate($company, $payment->plan, $payment->billing_cycle);
            $payment->update([
                'status' => 'approved',
                'paid_at' => $payment->paid_at ?? now(),
                'approved_by' => $approver?->id,
                'subscription_id' => $subscription->id,
            ]);
        });
    }

    /**
     * Uso actual de la empresa frente a los límites del plan.
     */
    public function usage(Company $company): array
    {
        $plan = $company->subscription?->plan;

        $usage = [
            'users' => ['label' => 'Usuarios', 'used' => User::where('company_id', $company->id)->count(), 'limit' => $plan?->max_users],
            'branches' => ['label' => 'Sucursales', 'used' => Branch::withoutGlobalScopes()->where('company_id', $company->id)->count(), 'limit' => $plan?->max_branches],
            'invoices' => ['label' => 'Facturas este mes', 'used' => Document::withoutGlobalScopes()->where('company_id', $company->id)->where('type', 'invoice')->where('created_at', '>=', now()->startOfMonth())->count(), 'limit' => $plan?->max_invoices],
            'products' => ['label' => 'Productos', 'used' => Product::withoutGlobalScopes()->where('company_id', $company->id)->count(), 'limit' => $plan?->max_products],
        ];

        foreach ($usage as &$row) {
            $row['percent'] = $row['limit'] ? min(100, (int) round($row['used'] / max(1, $row['limit']) * 100)) : 0;
        }

        return $usage;
    }

    public function canCreate(Company $company, string $resource): bool
    {
        $row = $this->usage($company)[$resource] ?? null;

        return ! $row || $row['limit'] === null || $row['used'] < $row['limit'];
    }

    public function limitMessage(string $resource): string
    {
        $labels = ['users' => 'usuarios', 'branches' => 'sucursales', 'invoices' => 'facturas mensuales', 'products' => 'productos'];

        return 'Ha alcanzado el límite de '.($labels[$resource] ?? $resource).' de su plan. Actualice su suscripción para continuar.';
    }
}
