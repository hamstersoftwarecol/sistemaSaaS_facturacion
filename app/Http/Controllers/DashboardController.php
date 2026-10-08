<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Cheque;
use App\Models\Document;
use App\Models\Expense;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        if (! $request->user()->can('dashboard.view')) {
            return view('dashboard', ['limited' => true]);
        }

        $monthStart = now()->startOfMonth();
        $invoices = fn () => Document::ofType('invoice')->forCurrentBranch()->where('status', '!=', 'cancelled');

        $salesMonth = (float) $invoices()->whereDate('date', '>=', $monthStart)->sum('total');
        $salesLastMonth = (float) $invoices()->whereBetween('date', [$monthStart->copy()->subMonth()->toDateString(), $monthStart->copy()->subDay()->toDateString()])->sum('total');

        // Serie de 12 meses: ventas, cobros y gastos.
        $months = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $since = $months->first()->toDateString();
        $salesByMonth = $invoices()->whereDate('date', '>=', $since)->get(['date', 'total'])->groupBy(fn ($d) => $d->date->format('Y-m'))->map->sum('total');
        $expensesByMonth = Expense::forCurrentBranch()->whereDate('date', '>=', $since)->get(['date', 'amount'])->groupBy(fn ($e) => $e->date->format('Y-m'))->map->sum('amount');
        $collectedByMonth = Payment::where('direction', 'in')->forCurrentBranch()->whereDate('date', '>=', $since)->get(['date', 'amount'])->groupBy(fn ($p) => $p->date->format('Y-m'))->map->sum('amount');

        $chart = [
            'labels' => $months->map(fn (Carbon $m) => ucfirst($m->locale('es')->translatedFormat('M y')))->values(),
            'sales' => $months->map(fn ($m) => round((float) ($salesByMonth[$m->format('Y-m')] ?? 0), 2))->values(),
            'collected' => $months->map(fn ($m) => round((float) ($collectedByMonth[$m->format('Y-m')] ?? 0), 2))->values(),
            'expenses' => $months->map(fn ($m) => round((float) ($expensesByMonth[$m->format('Y-m')] ?? 0), 2))->values(),
        ];

        $statusBreakdown = $invoices()->selectRaw('status, COUNT(*) as count, SUM(total) as total')->groupBy('status')->get()->keyBy('status');

        $topCustomers = $invoices()->whereDate('date', '>=', now()->subMonths(12))->with('customer')
            ->selectRaw('customer_id, SUM(total) as total, COUNT(*) as count')->groupBy('customer_id')->orderByDesc('total')->limit(5)->get();

        $lowStock = Product::with('stocks')->where('type', 'product')->where('track_stock', true)->where('is_active', true)->get()
            ->filter->isLowStock()->take(6);

        return view('dashboard', [
            'limited' => false,
            'salesMonth' => $salesMonth,
            'salesGrowth' => $salesLastMonth > 0 ? ($salesMonth - $salesLastMonth) / $salesLastMonth * 100 : null,
            'collectedMonth' => (float) Payment::where('direction', 'in')->forCurrentBranch()->whereDate('date', '>=', $monthStart)->sum('amount'),
            'receivables' => (float) $invoices()->whereIn('status', ['unpaid', 'partial'])->sum('balance'),
            'overdue' => (float) $invoices()->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->sum('balance'),
            'payables' => (float) Document::ofType('purchase_order')->forCurrentBranch()->where('status', 'received')->sum('balance'),
            'expensesMonth' => (float) Expense::forCurrentBranch()->whereDate('date', '>=', $monthStart)->sum('amount'),
            'cashBalance' => Account::where('is_active', true)->get()->sum(fn ($a) => $a->balance()),
            'chart' => $chart,
            'statusBreakdown' => $statusBreakdown,
            'recentInvoices' => $invoices()->with('customer')->latest('date')->latest('id')->limit(8)->get(),
            'overdueInvoices' => $invoices()->with('customer')->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->orderBy('due_date')->limit(5)->get(),
            'topCustomers' => $topCustomers,
            'lowStock' => $lowStock,
            'pendingQuotes' => Document::ofType('quote')->forCurrentBranch()->whereIn('status', ['draft', 'sent'])->count(),
            'chequesDue' => Cheque::forCurrentBranch()->whereIn('status', ['pending', 'deposited'])->whereDate('due_date', '<=', today()->addDays(7))->count(),
            'installmentsDue' => LoanInstallment::whereHas('loan', fn ($q) => $q->where('status', 'active'))->where('status', '!=', 'paid')->whereDate('due_date', '<=', today()->addDays(7))->count(),
        ]);
    }
}
