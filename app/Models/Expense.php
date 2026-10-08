<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\SyncsAccountTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToBranch, BelongsToCompany, SyncsAccountTransaction;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'date' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactionPayload(): ?array
    {
        return [
            'account_id' => $this->account_id,
            'direction' => 'out',
            'amount' => $this->amount,
            'date' => $this->date,
            'type' => 'expense',
            'description' => 'Gasto: '.$this->description,
            'reference' => $this->reference,
        ];
    }
}
