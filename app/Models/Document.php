<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Documento comercial genérico. El campo "type" determina si es un
 * presupuesto, factura, albarán, nota de crédito, orden de compra o nota de débito.
 */
class Document extends Model
{
    use BelongsToBranch, BelongsToCompany;

    protected $guarded = ['id'];

    /**
     * stock: -1 descuenta inventario, 1 lo incrementa, 0 no lo afecta.
     */
    public const TYPES = [
        'quote' => [
            'label' => 'Presupuesto', 'gender' => 'm', 'plural' => 'Presupuestos', 'route' => 'quotes', 'module' => 'quotes',
            'party' => 'customer', 'prefix' => 'PRE-', 'title' => 'PRESUPUESTO', 'stock' => 0, 'icon' => 'clipboard',
            'statuses' => ['draft' => ['Borrador', 'gray'], 'sent' => ['Enviado', 'blue'], 'accepted' => ['Aceptado', 'green'], 'rejected' => ['Rechazado', 'red'], 'converted' => ['Facturado', 'indigo']],
            'default_status' => 'draft',
        ],
        'invoice' => [
            'label' => 'Factura', 'gender' => 'f', 'plural' => 'Facturas', 'route' => 'invoices', 'module' => 'invoices',
            'party' => 'customer', 'prefix' => 'FAC-', 'title' => 'FACTURA', 'stock' => -1, 'icon' => 'document',
            'statuses' => ['unpaid' => ['Pendiente', 'yellow'], 'partial' => ['Pago parcial', 'blue'], 'paid' => ['Pagada', 'green'], 'cancelled' => ['Anulada', 'gray']],
            'default_status' => 'unpaid',
        ],
        'delivery_note' => [
            'label' => 'Albarán', 'gender' => 'm', 'plural' => 'Albaranes', 'route' => 'delivery-notes', 'module' => 'delivery_notes',
            'party' => 'customer', 'prefix' => 'ALB-', 'title' => 'ALBARÁN', 'stock' => -1, 'icon' => 'truck',
            'statuses' => ['pending' => ['Pendiente', 'yellow'], 'delivered' => ['Entregado', 'green'], 'invoiced' => ['Facturado', 'indigo'], 'cancelled' => ['Anulado', 'gray']],
            'default_status' => 'pending',
        ],
        'credit_note' => [
            'label' => 'Nota de crédito', 'gender' => 'f', 'plural' => 'Notas de crédito', 'route' => 'credit-notes', 'module' => 'credit_notes',
            'party' => 'customer', 'prefix' => 'NC-', 'title' => 'NOTA DE CRÉDITO', 'stock' => 1, 'icon' => 'receipt-refund',
            'statuses' => ['issued' => ['Emitida', 'blue'], 'applied' => ['Aplicada', 'green'], 'cancelled' => ['Anulada', 'gray']],
            'default_status' => 'issued',
        ],
        'purchase_order' => [
            'label' => 'Orden de compra', 'gender' => 'f', 'plural' => 'Órdenes de compra', 'route' => 'purchase-orders', 'module' => 'purchase_orders',
            'party' => 'supplier', 'prefix' => 'OC-', 'title' => 'ORDEN DE COMPRA', 'stock' => 1, 'icon' => 'shopping-cart',
            'statuses' => ['draft' => ['Borrador', 'gray'], 'ordered' => ['Pedida', 'blue'], 'received' => ['Recibida', 'green'], 'cancelled' => ['Anulada', 'gray']],
            'default_status' => 'ordered',
        ],
        'debit_note' => [
            'label' => 'Nota de débito', 'gender' => 'f', 'plural' => 'Notas de débito', 'route' => 'debit-notes', 'module' => 'debit_notes',
            'party' => 'supplier', 'prefix' => 'ND-', 'title' => 'NOTA DE DÉBITO', 'stock' => -1, 'icon' => 'receipt-tax',
            'statuses' => ['issued' => ['Emitida', 'blue'], 'applied' => ['Aplicada', 'green'], 'cancelled' => ['Anulada', 'gray']],
            'default_status' => 'issued',
        ],
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'adjustments_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'adjustments' => 'array',
            'custom_fields' => 'array',
            'stock_applied' => 'boolean',
            'affects_stock' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            $document->verification_code ??= Str::lower(Str::random(32));
        });
    }

    public static function config(string $type, ?string $key = null)
    {
        $config = self::TYPES[$type] ?? abort(404);

        return $key ? $config[$key] : $config;
    }

    /**
     * Concordancia de género: "Nueva factura", "Nuevo presupuesto", "creada", "creado"...
     */
    public static function phrase(string $type, string $word): string
    {
        $feminine = (self::TYPES[$type]['gender'] ?? 'm') === 'f';
        $label = mb_strtolower(self::TYPES[$type]['label']);

        return match ($word) {
            'new' => ($feminine ? 'Nueva ' : 'Nuevo ').$label,
            'created' => $feminine ? 'creada' : 'creado',
            'updated' => $feminine ? 'actualizada' : 'actualizado',
            'deleted' => $feminine ? 'eliminada' : 'eliminado',
            'generated' => $feminine ? 'generada' : 'generado',
            default => $word,
        };
    }

    public function cfg(?string $key = null)
    {
        return static::config($this->type, $key);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Document::class, 'parent_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function party(): Customer|Supplier|null
    {
        return $this->cfg('party') === 'supplier' ? $this->supplier : $this->customer;
    }

    public function isSales(): bool
    {
        return $this->cfg('party') === 'customer';
    }

    public function isPayable(): bool
    {
        return in_array($this->type, ['invoice', 'purchase_order'])
            && $this->status !== 'cancelled'
            && ($this->type === 'invoice' || $this->status === 'received');
    }

    public function isOverdue(): bool
    {
        return $this->type === 'invoice'
            && in_array($this->status, ['unpaid', 'partial'])
            && $this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public function statusLabel(): string
    {
        if ($this->isOverdue()) {
            return 'Vencida';
        }

        return $this->cfg('statuses')[$this->status][0] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        if ($this->isOverdue()) {
            return 'red';
        }

        return $this->cfg('statuses')[$this->status][1] ?? 'gray';
    }

    /**
     * Estado de pago de una orden de compra recibida.
     */
    public function paymentStatusLabel(): string
    {
        if ((float) $this->total <= 0 || (float) $this->balance <= 0.004) {
            return 'Pagada';
        }

        return (float) $this->amount_paid > 0 ? 'Pago parcial' : 'Pendiente de pago';
    }

    public function routeName(string $action): string
    {
        return $this->cfg('route').'.'.$action;
    }

    public function verificationUrl(): string
    {
        return route('verify.document', $this->verification_code);
    }
}
