<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const TYPES = ['cash' => 'Efectivo / Caja', 'bank' => 'Cuenta bancaria'];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(Reconciliation::class);
    }

    public function balance(?string $untilDate = null): float
    {
        $query = $this->transactions();
        if ($untilDate) {
            $query->whereDate('date', '<=', $untilDate);
        }

        $in = (float) (clone $query)->where('direction', 'in')->sum('amount');
        $out = (float) (clone $query)->where('direction', 'out')->sum('amount');

        return round((float) $this->opening_balance + $in - $out, 2);
    }

    public function reconciledBalance(): float
    {
        $in = (float) $this->transactions()->whereNotNull('reconciled_at')->where('direction', 'in')->sum('amount');
        $out = (float) $this->transactions()->whereNotNull('reconciled_at')->where('direction', 'out')->sum('amount');

        return round((float) $this->opening_balance + $in - $out, 2);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('name');
    }
}
