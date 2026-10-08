<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\TaxRate;
use App\Services\DocumentService;
use App\Services\SubscriptionService;
use App\Support\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Facturación rápida (tipo punto de venta) operada completamente con el teclado.
 */
class QuickInvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:invoices.create')];
    }

    public function create(): View
    {
        $defaultCustomer = Customer::where('is_active', true)->where('name', 'like', '%consumidor final%')->first()
            ?? Customer::where('is_active', true)->orderBy('id')->first();

        return view('invoices.quick', [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(['id', 'name', 'tax_id']),
            'defaultCustomerId' => $defaultCustomer?->id,
            'accounts' => Account::active()->get(['id', 'name', 'type']),
            'defaultAccountId' => Account::active()->where('type', 'cash')->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', Tenant::workingBranchId()))->value('id')
                ?? Account::active()->value('id'),
            'taxRates' => TaxRate::where('is_active', true)->get(['id', 'name', 'rate']),
        ]);
    }

    public function store(Request $request, DocumentService $documents, SubscriptionService $subscriptions): JsonResponse
    {
        if (! $subscriptions->canCreate(Tenant::company(), 'invoices')) {
            return response()->json(['message' => $subscriptions->limitMessage('invoices')], 422);
        }

        $companyId = Tenant::id();
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'items.*.tax_rate_id' => ['nullable', Rule::exists('tax_rates', 'id')->where('company_id', $companyId)],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'account_id' => ['nullable', 'required_with:paid_amount', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'method' => ['nullable', Rule::in(array_keys(Payment::METHODS))],
        ], ['items.required' => 'Agregue al menos un producto.']);

        $invoice = DB::transaction(function () use ($data, $documents) {
            $customer = Customer::find($data['customer_id']);
            $invoice = $documents->save('invoice', [
                'customer_id' => $customer->id,
                'price_list_id' => $customer->price_list_id,
                'date' => today()->toDateString(),
                'due_date' => today()->addDays($customer->payment_terms)->toDateString(),
                'notes' => Tenant::company()->invoice_notes,
                'items' => $data['items'],
            ]);

            $paid = min((float) ($data['paid_amount'] ?? 0), (float) $invoice->total);
            if ($paid > 0 && ! empty($data['account_id'])) {
                $sequence = (int) Payment::where('direction', 'in')->max('sequence') + 1;
                Payment::create([
                    'direction' => 'in',
                    'sequence' => $sequence,
                    'number' => 'RC-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'document_id' => $invoice->id,
                    'account_id' => $data['account_id'],
                    'method' => $data['method'] ?? 'cash',
                    'amount' => $paid,
                    'date' => today(),
                    'notes' => 'Cobro en facturación rápida',
                ]);
                $documents->refreshBalance($invoice);
            }

            return $invoice->fresh();
        });

        return response()->json([
            'message' => 'Factura '.$invoice->number.' creada por '.money($invoice->total),
            'number' => $invoice->number,
            'total' => (float) $invoice->total,
            'show_url' => route('invoices.show', $invoice),
            'print_url' => route('invoices.print', $invoice),
        ]);
    }
}
