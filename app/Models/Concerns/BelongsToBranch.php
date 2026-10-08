<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registros operativos asociados a una sucursal.
 */
trait BelongsToBranch
{
    protected static function bootBelongsToBranch(): void
    {
        static::creating(function ($model) {
            if (empty($model->branch_id)) {
                $model->branch_id = Tenant::workingBranchId();
            }
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Filtra por la sucursal seleccionada en la sesión (si hay una).
     */
    public function scopeForCurrentBranch(Builder $query): Builder
    {
        if ($branchId = Tenant::branchId()) {
            $query->where($this->getTable().'.branch_id', $branchId);
        }

        return $query;
    }
}
