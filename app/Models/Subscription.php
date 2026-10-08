<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const STATUSES = [
        'trialing' => ['Periodo de prueba', 'blue'],
        'active' => ['Activa', 'green'],
        'pending' => ['Pago pendiente', 'yellow'],
        'past_due' => ['Vencida', 'red'],
        'cancelled' => ['Cancelada', 'gray'],
        'expired' => ['Expirada', 'red'],
    ];

    public const CYCLES = ['monthly' => 'Mensual', 'yearly' => 'Anual'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isValid(): bool
    {
        return match ($this->status) {
            'trialing' => ! $this->trial_ends_at || $this->trial_ends_at->isFuture(),
            'active' => ! $this->ends_at || $this->ends_at->endOfDay()->isFuture(),
            default => false,
        };
    }

    public function expiresAt()
    {
        return $this->status === 'trialing' ? $this->trial_ends_at : $this->ends_at;
    }

    public function daysLeft(): ?int
    {
        $date = $this->expiresAt();

        return $date ? max(0, (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false)) : null;
    }

    public function statusLabel(): string
    {
        if (in_array($this->status, ['trialing', 'active']) && ! $this->isValid()) {
            return 'Expirada';
        }

        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        if (in_array($this->status, ['trialing', 'active']) && ! $this->isValid()) {
            return 'red';
        }

        return self::STATUSES[$this->status][1] ?? 'gray';
    }

    public function cycleLabel(): string
    {
        return self::CYCLES[$this->billing_cycle] ?? $this->billing_cycle;
    }
}
