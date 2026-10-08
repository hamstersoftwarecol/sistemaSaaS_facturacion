<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', ['plans' => Plan::withCount('subscriptions')->orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan(['trial_days' => 14, 'is_active' => true, 'features' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Plan::create($this->validated($request));

        return redirect()->route('admin.plans.index')->with('success', 'Plan creado.');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validated($request, $plan));

        return redirect()->route('admin.plans.index')->with('success', 'Plan actualizado.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists() || SubscriptionPayment::withoutGlobalScopes()->where('plan_id', $plan->id)->exists()) {
            $plan->update(['is_active' => false]);

            return back()->with('warning', 'El plan tiene suscripciones: se ha desactivado en lugar de eliminarse.');
        }

        $plan->delete();

        return back()->with('success', 'Plan eliminado.');
    }

    protected function validated(Request $request, ?Plan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'slug' => ['nullable', 'alpha_dash', 'max:60', Rule::unique('plans')->ignore($plan?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'max_invoices' => ['nullable', 'integer', 'min:1'],
            'max_products' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [], ['name' => 'nombre', 'price_monthly' => 'precio mensual', 'price_yearly' => 'precio anual']);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['features'] = collect(preg_split('/\r?\n/', (string) ($data['features'] ?? '')))->map(fn ($f) => trim($f))->filter()->values()->all();
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
