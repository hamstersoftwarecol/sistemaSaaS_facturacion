<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Libro mayor (estado de cuenta) de clientes y proveedores.
 */
class LedgerService
{
    public function customer(Customer $customer, ?string $from = null, ?string $to = null): array
    {
        $entries = collect();

        Document::where('customer_id', $customer->id)->whereIn('type', ['invoice', 'credit_note'])
            ->where('status', '!=', 'cancelled')->get()
            ->each(function (Document $doc) use ($entries) {
                $isInvoice = $doc->type === 'invoice';
                $entries->push([
                    'date' => $doc->date->toDateString(),
                    'order' => $doc->created_at?->timestamp ?? 0,
                    'type' => $doc->cfg('label'),
                    'number' => $doc->number,
                    'description' => $isInvoice ? 'Factura emitida' : 'Nota de crédito'.($doc->parent ? ' s/ '.$doc->parent->number : ''),
                    'debit' => $isInvoice ? (float) $doc->total : 0,
                    'credit' => $isInvoice ? 0 : (float) $doc->total,
                    'url' => route($doc->routeName('show'), $doc),
                ]);
            });

        Payment::where('direction', 'in')->where('customer_id', $customer->id)->with('document')->get()
            ->each(function (Payment $payment) use ($entries) {
                $entries->push([
                    'date' => $payment->date->toDateString(),
                    'order' => $payment->created_at?->timestamp ?? 0,
                    'type' => 'Cobro',
                    'number' => $payment->number,
                    'description' => $payment->methodLabel().($payment->document ? ' · '.$payment->document->number : ''),
                    'debit' => 0,
                    'credit' => (float) $payment->amount,
                    'url' => route('payments-received.show', $payment),
                ]);
            });

        return $this->build($entries, (float) $customer->opening_balance, $from, $to, debitIncreases: true);
    }

    public function supplier(Supplier $supplier, ?string $from = null, ?string $to = null): array
    {
        $entries = collect();

        Document::where('supplier_id', $supplier->id)
            ->where(fn ($q) => $q->where(fn ($q) => $q->where('type', 'purchase_order')->where('status', 'received'))
                ->orWhere(fn ($q) => $q->where('type', 'debit_note')->where('status', '!=', 'cancelled')))
            ->get()
            ->each(function (Document $doc) use ($entries) {
                $isPurchase = $doc->type === 'purchase_order';
                $entries->push([
                    'date' => $doc->date->toDateString(),
                    'order' => $doc->created_at?->timestamp ?? 0,
                    'type' => $doc->cfg('label'),
                    'number' => $doc->number,
                    'description' => $isPurchase ? 'Compra recibida' : 'Devolución / nota de débito',
                    'debit' => $isPurchase ? 0 : (float) $doc->total,
                    'credit' => $isPurchase ? (float) $doc->total : 0,
                    'url' => route($doc->routeName('show'), $doc),
                ]);
            });

        Payment::where('direction', 'out')->where('supplier_id', $supplier->id)->with('document')->get()
            ->each(function (Payment $payment) use ($entries) {
                $entries->push([
                    'date' => $payment->date->toDateString(),
                    'order' => $payment->created_at?->timestamp ?? 0,
                    'type' => 'Pago',
                    'number' => $payment->number,
                    'description' => $payment->methodLabel().($payment->document ? ' · '.$payment->document->number : ''),
                    'debit' => (float) $payment->amount,
                    'credit' => 0,
                    'url' => route('payments-made.show', $payment),
                ]);
            });

        return $this->build($entries, (float) $supplier->opening_balance, $from, $to, debitIncreases: false);
    }

    protected function build(Collection $entries, float $opening, ?string $from, ?string $to, bool $debitIncreases): array
    {
        $sign = fn (array $e) => $debitIncreases ? $e['debit'] - $e['credit'] : $e['credit'] - $e['debit'];
        $entries = $entries->sortBy([['date', 'asc'], ['order', 'asc']])->values();

        $balance = $opening;
        if ($from) {
            $balance += $entries->filter(fn ($e) => $e['date'] < $from)->sum($sign);
            $entries = $entries->filter(fn ($e) => $e['date'] >= $from);
        }
        if ($to) {
            $entries = $entries->filter(fn ($e) => $e['date'] <= $to);
        }

        $openingForPeriod = $balance;
        $rows = $entries->map(function ($entry) use (&$balance, $sign) {
            $balance += $sign($entry);
            $entry['balance'] = round($balance, 2);

            return $entry;
        })->values();

        return [
            'opening' => round($openingForPeriod, 2),
            'rows' => $rows,
            'debit' => round($rows->sum('debit'), 2),
            'credit' => round($rows->sum('credit'), 2),
            'closing' => round($balance, 2),
        ];
    }
}
