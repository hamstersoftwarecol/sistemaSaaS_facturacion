<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\SyncsAccountTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    use BelongsToBranch, BelongsToCompany, SyncsAccountTransaction;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'date' => 'date'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactionPayload(): ?array
    {
        return [
            'account_id' => $this->account_id,
            'direction' => 'in',
            'amount' => $this->amount,
            'date' => $this->date,
            'type' => 'income',
            'description' => 'Ingreso: '.$this->description,
            'reference' => $this->reference,
        ];
    }
}
