<?php

namespace App\Models\Concerns;

use App\Models\AccountTransaction;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Mantiene sincronizado el movimiento de caja/banco generado por un registro
 * (pago, gasto, ingreso, cheque cobrado, cuota de préstamo...).
 */
trait SyncsAccountTransaction
{
    protected static function bootSyncsAccountTransaction(): void
    {
        static::saved(fn ($model) => $model->syncAccountTransaction());
        static::deleting(fn ($model) => $model->accountTransaction()->delete());
    }

    public function accountTransaction(): MorphOne
    {
        return $this->morphOne(AccountTransaction::class, 'source');
    }

    /**
     * Datos del movimiento: account_id, direction (in/out), amount, date, type, description, reference.
     * Devuelve null si el registro no debe generar movimiento.
     */
    abstract public function transactionPayload(): ?array;

    public function syncAccountTransaction(): void
    {
        $payload = $this->transactionPayload();
        $existing = $this->accountTransaction()->first();

        if (! $payload || empty($payload['account_id']) || (float) $payload['amount'] <= 0) {
            $existing?->delete();

            return;
        }

        if ($existing) {
            $existing->update($payload);
        } else {
            $this->accountTransaction()->create($payload + [
                'company_id' => $payload['company_id'] ?? $this->company_id,
                'user_id' => auth()->id(),
            ]);
        }
    }
}
