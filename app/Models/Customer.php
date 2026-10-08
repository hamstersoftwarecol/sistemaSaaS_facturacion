<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
            'custom_fields' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function invoices(): HasMany
    {
        return $this->documents()->where('type', 'invoice');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('direction', 'in');
    }

    /**
     * Saldo pendiente (cuentas por cobrar) del cliente.
     */
    public function balance(): float
    {
        $invoiced = (float) $this->documents()->where('type', 'invoice')->where('status', '!=', 'cancelled')->sum('total');
        $credited = (float) $this->documents()->where('type', 'credit_note')->where('status', '!=', 'cancelled')->sum('total');
        $paid = (float) $this->payments()->sum('amount');

        return round((float) $this->opening_balance + $invoiced - $credited - $paid, 2);
    }
}
