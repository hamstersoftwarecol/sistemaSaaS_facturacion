<?php

namespace App\Models;

use App\Models\Concerns\SyncsAccountTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanInstallment extends Model
{
    use SyncsAccountTransaction;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'paid_at' => 'date',
            'principal' => 'decimal:2',
            'interest' => 'decimal:2',
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date->lt(today());
    }

    public function transactionPayload(): ?array
    {
        if ($this->status !== 'paid') {
            return null;
        }

        $loan = $this->loan;

        return [
            'company_id' => $loan->company_id,
            'account_id' => $this->account_id,
            'direction' => $loan->type === 'borrowed' ? 'out' : 'in',
            'amount' => $this->amount,
            'date' => $this->paid_at ?? today(),
            'type' => 'installment',
            'description' => "Cuota {$this->number}/{$loan->installments_count} · {$loan->party_name}",
            'reference' => $loan->reference,
        ];
    }
}
