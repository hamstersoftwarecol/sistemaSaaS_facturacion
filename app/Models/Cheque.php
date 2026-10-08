<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\SyncsAccountTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    use BelongsToBranch, BelongsToCompany, SyncsAccountTransaction;

    protected $guarded = ['id'];

    public const TYPES = ['received' => 'Recibido (de cliente)', 'issued' => 'Emitido (a proveedor)'];

    public const STATUSES = [
        'pending' => ['En cartera', 'yellow'],
        'deposited' => ['Depositado', 'blue'],
        'cleared' => ['Cobrado / Compensado', 'green'],
        'bounced' => ['Rechazado', 'red'],
        'cancelled' => ['Anulado', 'gray'],
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'issue_date' => 'date', 'due_date' => 'date', 'cleared_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function partyName(): string
    {
        return $this->customer?->name ?? $this->supplier?->name ?? $this->party_name ?? '—';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function transactionPayload(): ?array
    {
        if ($this->status !== 'cleared') {
            return null;
        }

        return [
            'account_id' => $this->account_id,
            'direction' => $this->type === 'received' ? 'in' : 'out',
            'amount' => $this->amount,
            'date' => $this->cleared_at ?? $this->due_date,
            'type' => 'cheque',
            'description' => 'Cheque #'.$this->cheque_number.' · '.$this->partyName(),
            'reference' => $this->bank_name,
        ];
    }
}
