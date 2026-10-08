<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'custom_fields' => 'array', 'is_active' => 'boolean'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->where('direction', 'out');
    }

    /**
     * Saldo pendiente (cuentas por pagar) con el proveedor.
     */
    public function balance(): float
    {
        $purchased = (float) $this->documents()->where('type', 'purchase_order')->where('status', 'received')->sum('total');
        $returned = (float) $this->documents()->where('type', 'debit_note')->where('status', '!=', 'cancelled')->sum('total');
        $paid = (float) $this->payments()->sum('amount');

        return round((float) $this->opening_balance + $purchased - $returned - $paid, 2);
    }
}
