<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Subscription::with(['company', 'plan']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($plan = $request->integer('plan_id')) {
            $query->where('plan_id', $plan);
        }

        return view('admin.subscriptions.index', [
            'subscriptions' => $query->latest()->paginate(20)->withQueryString(),
            'plans' => Plan::orderBy('sort_order')->get(),
        ]);
    }

    public function edit(Subscription $subscription): View
    {
        return view('admin.subscriptions.edit', ['subscription' => $subscription->load('company'), 'plans' => Plan::orderBy('sort_order')->get()]);
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', Rule::in(array_keys(Subscription::CYCLES))],
            'status' => ['required', Rule::in(array_keys(Subscription::STATUSES))],
            'price' => ['required', 'numeric', 'min:0'],
            'trial_ends_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ], [], ['plan_id' => 'plan', 'ends_at' => 'fecha de vencimiento']);

        $data['cancelled_at'] = $data['status'] === 'cancelled' ? ($subscription->cancelled_at ?? now()) : null;
        $subscription->update($data);

        return redirect()->route('admin.subscriptions.index')->with('success', 'Suscripción actualizada.');
    }
}
