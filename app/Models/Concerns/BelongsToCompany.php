<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aísla los registros por empresa (multi-tenant) y asigna la empresa al crear.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if ($companyId = Tenant::id()) {
                $query->where($query->getModel()->getTable().'.company_id', $companyId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id) && $companyId = Tenant::id()) {
                $model->company_id = $companyId;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
