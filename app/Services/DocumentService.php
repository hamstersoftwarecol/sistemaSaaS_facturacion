<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use App\Models\Formula;
use App\Models\Product;
use App\Models\TaxRate;
use App\Support\FormulaEvaluator;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentService
{
    public function __construct(
        protected InventoryService $inventory,
        protected FormulaEvaluator $evaluator,
    ) {}

    /**
     * Calcula líneas, impuestos, fórmulas y totales de un documento.
     *
     * @param  array<int, array>  $items
     */
    public function calculate(array $items, string $type): array
    {
        $taxRates = TaxRate::pluck('rate', 'id');
        $products = Product::whereIn('id', collect($items)->pluck('product_id')->filter())->get()->keyBy('id');
        $lines = [];
        $totals = ['gross' => 0, 'discount' => 0, 'subtotal' => 0, 'tax' => 0, 'quantity' => 0];

        foreach (array_values($items) as $index => $item) {
            $quantity = round((float) ($item['quantity'] ?? 0), 3);
            $price = round((float) ($item['unit_price'] ?? 0), 2);
            $discountPercent = min(100, max(0, (float) ($item['discount_percent'] ?? 0)));
            $taxRateId = $item['tax_rate_id'] ?? null;
            $rate = $taxRateId ? (float) ($taxRates[$taxRateId] ?? 0) : 0.0;
            $product = isset($item['product_id']) ? $products->get($item['product_id']) : null;

            $gross = round($quantity * $price, 2);
            $discount = round($gross * $discountPercent / 100, 2);
            $subtotal = round($gross - $discount, 2);
            $tax = round($subtotal * $rate / 100, 2);

            $lines[] = [
                'product_id' => $product?->id,
                'tax_rate_id' => $taxRateId ?: null,
                'description' => trim((string) ($item['description'] ?? '')) ?: ($product?->name ?? 'Concepto'),
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? $product?->unit,
                'unit_price' => $price,
                'discount_percent' => $discountPercent,
                'tax_rate' => $rate,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total' => round($subtotal + $tax, 2),
                'unit_cost' => $product?->cost_price ?? 0,
                'sort_order' => $index,
            ];

            $totals['gross'] += $gross;
            $totals['discount'] += $discount;
            $totals['subtotal'] += $subtotal;
            $totals['tax'] += $tax;
            $totals['quantity'] += $quantity;
        }

        $variables = [
            'gross' => round($totals['gross'], 2),
            'discount' => round($totals['discount'], 2),
            'subtotal' => round($totals['subtotal'], 2),
            'tax' => round($totals['tax'], 2),
            'total' => round($totals['subtotal'] + $totals['tax'], 2),
            'items' => count($lines),
            'quantity' => $totals['quantity'],
        ];

        [$adjustments, $adjustmentsTotal] = $this->applyFormulas($type, $variables);

        return [
            'items' => $lines,
            'subtotal' => $variables['subtotal'],
            'discount_total' => $variables['discount'],
            'tax_total' => $variables['tax'],
            'adjustments' => $adjustments,
            'adjustments_total' => $adjustmentsTotal,
            'total' => round($variables['total'] + $adjustmentsTotal, 2),
        ];
    }

    protected function applyFormulas(string $type, array $variables): array
    {
        $adjustments = [];
        $total = 0.0;

        $formulas = Formula::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            ->filter(fn (Formula $formula) => $formula->appliesTo($type));

        foreach ($formulas as $formula) {
            try {
                $amount = round($this->evaluator->evaluate($formula->expression, $variables), 2);
            } catch (\Throwable) {
                continue;
            }

            $variables[strtolower($formula->code)] = $amount;

            if (abs($amount) < 0.005) {
                continue;
            }

            $signed = match ($formula->action) {
                'add' => $amount,
                'subtract' => -$amount,
                default => 0.0,
            };
            $total += $signed;

            $adjustments[] = [
                'code' => $formula->code,
                'name' => $formula->name,
                'action' => $formula->action,
                'amount' => $amount,
            ];
        }

        return [$adjustments, round($total, 2)];
    }

    public function nextNumber(string $type, ?Company $company = null): array
    {
        $company ??= Tenant::company();
        $sequence = (int) Document::withoutGlobalScopes()
            ->where('company_id', $company->id)->where('type', $type)->max('sequence') + 1;

        return [$sequence, $company->prefix($type).str_pad((string) $sequence, 5, '0', STR_PAD_LEFT)];
    }

    /**
     * Crea o actualiza un documento con sus líneas y efectos colaterales.
     */
    public function save(string $type, array $data, ?Document $document = null): Document
    {
        return DB::transaction(function () use ($type, $data, $document) {
            $calc = $this->calculate($data['items'] ?? [], $type);
            $config = Document::config($type);

            $header = collect($data)->only([
                'customer_id', 'supplier_id', 'parent_id', 'price_list_id', 'reference', 'date', 'due_date',
                'notes', 'terms', 'custom_fields', 'branch_id', 'recurring_invoice_id', 'affects_stock',
            ])->all();

            if (isset($data['status']) && array_key_exists($data['status'], $config['statuses'])) {
                $header['status'] = $data['status'];
            }

            $header += [
                'subtotal' => $calc['subtotal'],
                'discount_total' => $calc['discount_total'],
                'tax_total' => $calc['tax_total'],
                'adjustments' => $calc['adjustments'],
                'adjustments_total' => $calc['adjustments_total'],
                'total' => $calc['total'],
            ];

            if (! $document) {
                [$sequence, $number] = $this->nextNumber($type);
                $document = new Document(['type' => $type, 'sequence' => $sequence, 'number' => $number]);
                $document->status = $header['status'] ?? $config['default_status'];
            }

            $previousParentId = $document->parent_id;
            $document->fill($header);
            $document->type = $type;
            $document->save();

            $document->items()->delete();
            $document->items()->createMany($calc['items']);
            $document->unsetRelation('items');

            $this->inventory->syncDocument($document->fresh());
            $this->refreshBalance($document);

            foreach (array_unique(array_filter([$previousParentId, $document->parent_id])) as $parentId) {
                if ($parent = Document::find($parentId)) {
                    $this->refreshBalance($parent);
                }
            }

            return $document->fresh(['items']);
        });
    }

    /**
     * Recalcula importe pagado, saldo y estado de pago.
     */
    public function refreshBalance(Document $document): void
    {
        $document->refresh();
        $total = (float) $document->total;

        switch ($document->type) {
            case 'invoice':
            case 'purchase_order':
                $relatedNote = $document->type === 'invoice' ? 'credit_note' : 'debit_note';
                $paid = (float) $document->payments()->sum('amount')
                    + (float) Document::where('parent_id', $document->id)->where('type', $relatedNote)
                        ->where('status', '!=', 'cancelled')->sum('total');
                $document->amount_paid = round($paid, 2);
                $document->balance = round($total - $paid, 2);

                if ($document->type === 'invoice' && $document->status !== 'cancelled') {
                    $document->status = $document->balance <= 0.004 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
                }
                break;

            case 'credit_note':
            case 'debit_note':
                if ($document->status !== 'cancelled') {
                    $document->status = $document->parent_id ? 'applied' : 'issued';
                }
                $document->amount_paid = $document->parent_id ? $total : 0;
                $document->balance = round($total - (float) $document->amount_paid, 2);
                break;

            default:
                $document->amount_paid = 0;
                $document->balance = $total;
        }

        $document->saveQuietly();
    }

    /**
     * Convierte un documento en otro tipo (p. ej. presupuesto → factura).
     */
    public function convert(Document $source, string $targetType): Document
    {
        $allowed = self::CONVERSIONS[$source->type] ?? [];
        if (! in_array($targetType, $allowed, true)) {
            throw ValidationException::withMessages(['type' => 'Conversión no permitida.']);
        }

        return DB::transaction(function () use ($source, $targetType) {
            $company = Tenant::company() ?? $source->company;
            $items = $source->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'discount_percent' => $item->discount_percent,
                'tax_rate_id' => $item->tax_rate_id,
            ])->all();

            $partyTerms = $source->customer?->payment_terms ?? $source->supplier?->payment_terms ?? 0;

            $document = $this->save($targetType, [
                'customer_id' => $source->customer_id,
                'supplier_id' => $source->supplier_id,
                'parent_id' => $source->id,
                'price_list_id' => $source->price_list_id,
                'branch_id' => $source->branch_id,
                'reference' => $source->number,
                'date' => today()->toDateString(),
                'due_date' => $targetType === 'invoice' ? today()->addDays($partyTerms ?: 30)->toDateString() : null,
                'notes' => $source->notes,
                'terms' => $targetType === 'invoice' ? ($company->invoice_terms ?? $source->terms) : $source->terms,
                'custom_fields' => $source->custom_fields,
                'items' => $items,
            ]);

            $newStatus = match ([$source->type, $targetType]) {
                ['quote', 'invoice'], ['quote', 'delivery_note'] => 'converted',
                ['delivery_note', 'invoice'] => 'invoiced',
                default => null,
            };

            if ($newStatus) {
                $source->forceFill(['status' => $newStatus])->saveQuietly();
            }

            return $document;
        });
    }

    public const CONVERSIONS = [
        'quote' => ['invoice', 'delivery_note'],
        'delivery_note' => ['invoice'],
        'invoice' => ['credit_note'],
        'purchase_order' => ['debit_note'],
    ];

    public function duplicate(Document $source): Document
    {
        $items = $source->items->map(fn ($item) => $item->only([
            'product_id', 'description', 'quantity', 'unit', 'unit_price', 'discount_percent', 'tax_rate_id',
        ]))->all();

        return $this->save($source->type, [
            'customer_id' => $source->customer_id,
            'supplier_id' => $source->supplier_id,
            'price_list_id' => $source->price_list_id,
            'date' => today()->toDateString(),
            'due_date' => $source->due_date ? today()->addDays($source->date->diffInDays($source->due_date))->toDateString() : null,
            'notes' => $source->notes,
            'terms' => $source->terms,
            'custom_fields' => $source->custom_fields,
            'affects_stock' => $source->affects_stock,
            'items' => $items,
        ]);
    }

    public function changeStatus(Document $document, string $status): void
    {
        if (! array_key_exists($status, $document->cfg('statuses'))) {
            throw ValidationException::withMessages(['status' => 'Estado no válido.']);
        }

        if ($status === 'cancelled' && $document->payments()->exists()) {
            throw ValidationException::withMessages(['status' => 'No se puede anular un documento con pagos registrados. Elimine los pagos primero.']);
        }

        DB::transaction(function () use ($document, $status) {
            $document->update(['status' => $status]);
            $this->inventory->syncDocument($document->fresh());
            $this->refreshBalance($document);
            if ($document->parent) {
                $this->refreshBalance($document->parent);
            }
        });
    }

    public function delete(Document $document): void
    {
        if ($document->payments()->exists()) {
            throw ValidationException::withMessages(['document' => 'El documento tiene pagos registrados. Elimine los pagos antes de borrarlo.']);
        }

        DB::transaction(function () use ($document) {
            $parent = $document->parent;
            $this->inventory->revertDocument($document);
            $document->items()->delete();
            $document->delete();

            if ($parent) {
                $this->refreshBalance($parent);
            }
        });
    }
}
