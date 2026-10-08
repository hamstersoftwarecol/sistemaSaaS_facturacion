<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Document;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $subscriptions = Subscription::with('plan')->get();
        $active = $subscriptions->filter(fn ($s) => $s->status === 'active' && $s->isValid());

        $mrr = $active->sum(fn ($s) => $s->billing_cycle === 'yearly' ? (float) $s->price / 12 : (float) $s->price);

        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $revenue = SubscriptionPayment::where('status', 'approved')->where('paid_at', '>=', $months->first())->get()
            ->groupBy(fn ($p) => $p->paid_at->format('Y-m'))->map->sum('amount');
        $signups = Company::where('created_at', '>=', $months->first())->get()->groupBy(fn ($c) => $c->created_at->format('Y-m'))->map->count();

        return view('admin.dashboard', [
            'companies' => Company::count(),
            'activeCompanies' => Company::where('status', 'active')->count(),
            'users' => User::whereNotNull('company_id')->count(),
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'trialing' => $subscriptions->filter(fn ($s) => $s->status === 'trialing' && $s->isValid())->count(),
            'activeSubscriptions' => $active->count(),
            'expired' => $subscriptions->reject->isValid()->count(),
            'documents' => Document::withoutGlobalScopes()->where('type', 'invoice')->count(),
            'revenueTotal' => (float) SubscriptionPayment::where('status', 'approved')->sum('amount'),
            'pendingPayments' => SubscriptionPayment::with(['company', 'plan', 'paymentMethod'])->where('status', 'pending')->latest()->get(),
            'recentCompanies' => Company::with(['subscription.plan', 'owner'])->latest()->limit(8)->get(),
            'planDistribution' => Plan::withCount(['subscriptions' => fn ($q) => $q->whereIn('status', ['active', 'trialing'])])->orderBy('sort_order')->get(),
            'chart' => [
                'labels' => $months->map(fn ($m) => ucfirst($m->locale('es')->translatedFormat('M y')))->values(),
                'revenue' => $months->map(fn ($m) => round((float) ($revenue[$m->format('Y-m')] ?? 0), 2))->values(),
                'signups' => $months->map(fn ($m) => (int) ($signups[$m->format('Y-m')] ?? 0))->values(),
            ],
        ]);
    }
}
