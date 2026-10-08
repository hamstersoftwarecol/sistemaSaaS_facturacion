<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountTransaction extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const TYPES = [
        'deposit' => 'Depósito',
        'withdrawal' => 'Retiro',
        'transfer' => 'Transferencia',
        'payment_in' => 'Cobro a cliente',
        'payment_out' => 'Pago a proveedor',
        'expense' => 'Gasto',
        'income' => 'Otro ingreso',
        'cheque' => 'Cheque',
        'loan' => 'Préstamo',
        'installment' => 'Cuota de préstamo',
    ];

    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'decimal:2', 'reconciled_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function signedAmount(): float
    {
        return $this->direction === 'in' ? (float) $this->amount : -(float) $this->amount;
    }
}
