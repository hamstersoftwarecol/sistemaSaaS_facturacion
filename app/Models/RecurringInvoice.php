<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringInvoice extends Model
{
    use BelongsToBranch, BelongsToCompany;

    protected $guarded = ['id'];

    public const FREQUENCIES = [
        'daily' => 'Diaria',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'quarterly' => 'Trimestral',
        'yearly' => 'Anual',
    ];

    public const STATUSES = [
        'active' => ['Activa', 'green'],
        'paused' => ['Pausada', 'yellow'],
        'completed' => ['Finalizada', 'gray'],
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'next_date' => 'date',
            'end_date' => 'date',
            'items' => 'array',
            'total' => 'decimal:2',
            'last_generated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Document::class)->where('type', 'invoice');
    }

    public function advance(Carbon $date): Carbon
    {
        $interval = max(1, (int) $this->interval);

        return match ($this->frequency) {
            'daily' => $date->copy()->addDays($interval),
            'weekly' => $date->copy()->addWeeks($interval),
            'quarterly' => $date->copy()->addMonthsNoOverflow(3 * $interval),
            'yearly' => $date->copy()->addYearsNoOverflow($interval),
            default => $date->copy()->addMonthsNoOverflow($interval),
        };
    }

    public function isDue(): bool
    {
        return $this->status === 'active' && $this->next_date && $this->next_date->lte(today());
    }

    public function frequencyLabel(): string
    {
        $label = self::FREQUENCIES[$this->frequency] ?? $this->frequency;

        return $this->interval > 1 ? "{$label} (cada {$this->interval})" : $label;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUSES[$this->status][1] ?? 'gray';
    }
}
