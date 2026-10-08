<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Support\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Consultas de los informes de gestión.
 */
class ReportService
{
    protected function documents(string $type, string $from, string $to)
    {
        return Document::ofType($type)->forCurrentBranch()->where('status', '!=', 'cancelled')
            ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);
    }

    public function sales(string $from, string $to): array
    {
        $invoices = $this->documents('invoice', $from, $to)->with('customer')->orderBy('date')->get();
        $credits = $this->documents('credit_note', $from, $to)->get();
        $ids = $invoices->pluck('id');

        $byProduct = DocumentItem::whereIn('document_id', $ids)
            ->selectRaw('description, product_id, SUM(quantity) as quantity, SUM(subtotal) as subtotal, SUM(quantity * unit_cost) as cost')
            ->groupBy('product_id', 'description')->orderByDesc('subtotal')->limit(15)->get();

        return [
            'invoices' => $invoices,
            'count' => $invoices->count(),
            'subtotal' => $invoices->sum('subtotal'),
            'tax' => $invoices->sum('tax_total'),
            'total' => $invoices->sum('total'),
            'collected' => $invoices->sum('amount_paid'),
            'pending' => $invoices->sum('balance'),
            'credits' => $credits->sum('total'),
            'byCustomer' => $invoices->groupBy('customer_id')->map(fn ($g) => [
                'name' => $g->first()->customer?->name ?? '—',
                'count' => $g->count(),
                'total' => $g->sum('total'),
                'balance' => $g->sum('balance'),
            ])->sortByDesc('total')->take(15)->values(),
            'byProduct' => $byProduct,
            'byMonth' => $this->groupByMonth($invoices, 'total'),
        ];
    }

    public function purchases(string $from, string $to): array
    {
        $orders = $this->documents('purchase_order', $from, $to)->with('supplier')->orderBy('date')->get();
        $received = $orders->where('status', 'received');

        return [
            'orders' => $orders,
            'count' => $orders->count(),
            'total' => $received->sum('total'),
            'tax' => $received->sum('tax_total'),
            'paid' => $received->sum('amount_paid'),
            'pending' => $received->sum('balance'),
            'pendingReception' => $orders->whereIn('status', ['draft', 'ordered'])->sum('total'),
            'returns' => $this->documents('debit_note', $from, $to)->sum('total'),
            'bySupplier' => $received->groupBy('supplier_id')->map(fn ($g) => [
                'name' => $g->first()->supplier?->name ?? '—',
                'count' => $g->count(),
                'total' => $g->sum('total'),
                'balance' => $g->sum('balance'),
            ])->sortByDesc('total')->values(),
        ];
    }

    public function tax(string $from, string $to): array
    {
        $rows = function (array $types, int $sign) use ($from, $to) {
            return DocumentItem::query()
                ->whereHas('document', fn ($q) => $q->whereIn('type', $types)->forCurrentBranch()->where('status', '!=', 'cancelled')
                    ->when(in_array('purchase_order', $types), fn ($q) => $q->where(fn ($q) => $q->where('type', '!=', 'purchase_order')->orWhere('status', 'received')))
                    ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to))
                ->with('document:id,type')
                ->get()
                ->groupBy(fn ($item) => number_format((float) $item->tax_rate, 2))
                ->map(fn ($items, $rate) => [
                    'rate' => (float) $rate,
                    'base' => $items->sum(fn ($i) => (in_array($i->document->type, ['credit_note', 'debit_note']) ? -1 : 1) * (float) $i->subtotal),
                    'tax' => $items->sum(fn ($i) => (in_array($i->document->type, ['credit_note', 'debit_note']) ? -1 : 1) * (float) $i->tax_amount),
                ])->sortBy('rate')->values();
        };

        $sales = $rows(['invoice', 'credit_note'], 1);
        $purchases = $rows(['purchase_order', 'debit_note'], 1);
        $expenseTax = (float) Expense::forCurrentBranch()->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->sum('tax_amount');

        return [
            'sales' => $sales,
            'purchases' => $purchases,
            'salesTax' => $sales->sum('tax'),
            'purchasesTax' => $purchases->sum('tax') + $expenseTax,
            'expenseTax' => $expenseTax,
            'net' => $sales->sum('tax') - $purchases->sum('tax') - $expenseTax,
        ];
    }

    public function profitLoss(string $from, string $to): array
    {
        $invoiceIds = $this->documents('invoice', $from, $to)->pluck('id');
        $creditIds = $this->documents('credit_note', $from, $to)->pluck('id');

        $revenue = (float) DocumentItem::whereIn('document_id', $invoiceIds)->sum('subtotal');
        $returns = (float) DocumentItem::whereIn('document_id', $creditIds)->sum('subtotal');
        $cogs = (float) DocumentItem::whereIn('document_id', $invoiceIds)->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) as cost')->value('cost')
            - (float) DocumentItem::whereIn('document_id', $creditIds)->selectRaw('COALESCE(SUM(quantity * unit_cost), 0) as cost')->value('cost');

        $otherIncome = Income::forCurrentBranch()->with('category')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->get();
        $expenses = Expense::forCurrentBranch()->with('category')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->get();

        $netRevenue = $revenue - $returns;
        $gross = $netRevenue - $cogs;
        $expenseNet = $expenses->sum(fn ($e) => (float) $e->amount - (float) $e->tax_amount);
        $net = $gross + $otherIncome->sum('amount') - $expenseNet;

        return [
            'revenue' => $revenue,
            'returns' => $returns,
            'netRevenue' => $netRevenue,
            'cogs' => $cogs,
            'gross' => $gross,
            'grossMargin' => $netRevenue > 0 ? $gross / $netRevenue * 100 : 0,
            'otherIncome' => $otherIncome->groupBy(fn ($i) => $i->category?->name ?? 'Sin categoría')->map->sum('amount'),
            'otherIncomeTotal' => $otherIncome->sum('amount'),
            'expenses' => $expenses->groupBy(fn ($e) => $e->category?->name ?? 'Sin categoría')->map(fn ($g) => $g->sum(fn ($e) => (float) $e->amount - (float) $e->tax_amount))->sortDesc(),
            'expensesTotal' => $expenseNet,
            'net' => $net,
            'netMargin' => $netRevenue > 0 ? $net / $netRevenue * 100 : 0,
        ];
    }

    public function receivables(): array
    {
        $invoices = Document::ofType('invoice')->forCurrentBranch()->whereIn('status', ['unpaid', 'partial'])->where('balance', '>', 0)->with('customer')->get();

        $rows = $invoices->groupBy('customer_id')->map(function (Collection $group) {
            $buckets = ['current' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'over90' => 0];
            foreach ($group as $invoice) {
                $days = $invoice->due_date && $invoice->due_date->isPast() ? (int) $invoice->due_date->diffInDays(today()) : 0;
                $key = match (true) {
                    $days <= 0 => 'current',
                    $days <= 30 => 'd30',
                    $days <= 60 => 'd60',
                    $days <= 90 => 'd90',
                    default => 'over90',
                };
                $buckets[$key] += (float) $invoice->balance;
            }

            return ['party' => $group->first()->customer, 'count' => $group->count(), 'total' => $group->sum('balance')] + $buckets;
        })->sortByDesc('total')->values();

        return ['rows' => $rows, 'totals' => $this->sumBuckets($rows)];
    }

    public function payables(): array
    {
        $orders = Document::ofType('purchase_order')->forCurrentBranch()->where('status', 'received')->where('balance', '>', 0)->with('supplier')->get();

        $rows = $orders->groupBy('supplier_id')->map(function (Collection $group) {
            $buckets = ['current' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'over90' => 0];
            foreach ($group as $order) {
                $due = $order->due_date ?? $order->date;
                $days = $due->isPast() ? (int) $due->diffInDays(today()) : 0;
                $key = match (true) {
                    $days <= 0 => 'current',
                    $days <= 30 => 'd30',
                    $days <= 60 => 'd60',
                    $days <= 90 => 'd90',
                    default => 'over90',
                };
                $buckets[$key] += (float) $order->balance;
            }

            return ['party' => $group->first()->supplier, 'count' => $group->count(), 'total' => $group->sum('balance')] + $buckets;
        })->sortByDesc('total')->values();

        return ['rows' => $rows, 'totals' => $this->sumBuckets($rows)];
    }

    protected function sumBuckets(Collection $rows): array
    {
        return collect(['current', 'd30', 'd60', 'd90', 'over90', 'total'])->mapWithKeys(fn ($k) => [$k => $rows->sum($k)])->all();
    }

    public function inventory(): array
    {
        $branchId = Tenant::branchId();
        $products = Product::with(['stocks', 'category'])->where('type', 'product')->where('track_stock', true)->orderBy('name')->get()
            ->map(function (Product $p) use ($branchId) {
                $stock = $p->stockIn($branchId);

                return [
                    'product' => $p,
                    'stock' => $stock,
                    'cost_value' => $stock * (float) $p->cost_price,
                    'sale_value' => $stock * (float) $p->sale_price,
                    'low' => $stock <= (float) $p->min_stock,
                ];
            });

        return [
            'rows' => $products,
            'costValue' => $products->sum('cost_value'),
            'saleValue' => $products->sum('sale_value'),
            'units' => $products->sum('stock'),
            'lowCount' => $products->where('low', true)->count(),
            'branch' => $branchId ? Branch::find($branchId) : null,
        ];
    }

    public function cashflow(string $from, string $to): array
    {
        $accounts = Account::orderBy('name')->get()->map(function (Account $account) use ($from, $to) {
            $query = $account->transactions()->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);

            return [
                'account' => $account,
                'opening' => $account->balance(Carbon::parse($from)->subDay()->toDateString()),
                'in' => (float) (clone $query)->where('direction', 'in')->sum('amount'),
                'out' => (float) (clone $query)->where('direction', 'out')->sum('amount'),
                'closing' => $account->balance($to),
            ];
        });

        $byType = AccountTransaction::whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->selectRaw("type, SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) as inflow, SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) as outflow")
            ->groupBy('type')->get();

        return [
            'accounts' => $accounts,
            'byType' => $byType,
            'in' => $accounts->sum('in'),
            'out' => $accounts->sum('out'),
            'opening' => $accounts->sum('opening'),
            'closing' => $accounts->sum('closing'),
        ];
    }

    public function expenses(string $from, string $to): array
    {
        $expenses = Expense::forCurrentBranch()->with(['category', 'supplier', 'account'])
            ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->orderBy('date')->get();

        return [
            'expenses' => $expenses,
            'total' => $expenses->sum('amount'),
            'byCategory' => $expenses->groupBy(fn ($e) => $e->category?->name ?? 'Sin categoría')
                ->map(fn ($g) => ['count' => $g->count(), 'total' => $g->sum('amount')])->sortByDesc('total'),
            'byMonth' => $this->groupByMonth($expenses, 'amount'),
        ];
    }

    public function customers(string $from, string $to): array
    {
        $rows = Customer::orderBy('name')->get()->map(function (Customer $customer) use ($from, $to) {
            $invoices = Document::ofType('invoice')->where('customer_id', $customer->id)->where('status', '!=', 'cancelled')
                ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to);

            return [
                'customer' => $customer,
                'count' => (clone $invoices)->count(),
                'total' => (float) (clone $invoices)->sum('total'),
                'balance' => $customer->balance(),
            ];
        })->filter(fn ($row) => $row['count'] > 0 || abs($row['balance']) > 0.004)->sortByDesc('total')->values();

        return ['rows' => $rows, 'total' => $rows->sum('total'), 'balance' => $rows->sum('balance')];
    }

    protected function groupByMonth(Collection $items, string $field): Collection
    {
        return $items->groupBy(fn ($item) => $item->date->format('Y-m'))
            ->map(fn ($group) => $group->sum($field))->sortKeys();
    }
}
