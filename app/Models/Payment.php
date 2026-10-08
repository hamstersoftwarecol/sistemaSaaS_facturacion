<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\SyncsAccountTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToBranch, BelongsToCompany, SyncsAccountTransaction;

    protected $guarded = ['id'];

    public const METHODS = [
        'cash' => 'Efectivo',
        'bank_transfer' => 'Transferencia',
        'card' => 'Tarjeta',
        'cheque' => 'Cheque',
        'other' => 'Otro',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'date' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function party(): Customer|Supplier|null
    {
        return $this->direction === 'in' ? $this->customer : $this->supplier;
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    public function transactionPayload(): ?array
    {
        $party = $this->party()?->name;

        return [
            'account_id' => $this->account_id,
            'direction' => $this->direction,
            'amount' => $this->amount,
            'date' => $this->date,
            'type' => $this->direction === 'in' ? 'payment_in' : 'payment_out',
            'description' => ($this->direction === 'in' ? 'Cobro ' : 'Pago ').$this->number.($party ? " · {$party}" : ''),
            'reference' => $this->reference,
        ];
    }
}
