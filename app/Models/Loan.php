<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\SyncsAccountTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use BelongsToCompany, SyncsAccountTransaction;

    protected $guarded = ['id'];

    public const TYPES = ['borrowed' => 'Préstamo recibido (deuda)', 'lent' => 'Préstamo otorgado (por cobrar)'];

    public const FREQUENCIES = ['weekly' => 'Semanal', 'biweekly' => 'Quincenal', 'monthly' => 'Mensual', 'quarterly' => 'Trimestral'];

    public const STATUSES = ['active' => ['Activo', 'blue'], 'paid' => ['Liquidado', 'green'], 'cancelled' => ['Anulado', 'gray']];

    protected function casts(): array
    {
        return ['principal' => 'decimal:2', 'interest_rate' => 'decimal:2', 'start_date' => 'date'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)->orderBy('number');
    }

    public function totalAmount(): float
    {
        return (float) $this->installments->sum('amount');
    }

    public function paidAmount(): float
    {
        return (float) $this->installments->where('status', 'paid')->sum('amount');
    }

    public function outstanding(): float
    {
        return round($this->totalAmount() - $this->paidAmount(), 2);
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
        if ($this->status === 'cancelled') {
            return null;
        }

        return [
            'account_id' => $this->account_id,
            'direction' => $this->type === 'borrowed' ? 'in' : 'out',
            'amount' => $this->principal,
            'date' => $this->start_date,
            'type' => 'loan',
            'description' => ($this->type === 'borrowed' ? 'Préstamo recibido de ' : 'Préstamo otorgado a ').$this->party_name,
            'reference' => $this->reference,
        ];
    }
}
