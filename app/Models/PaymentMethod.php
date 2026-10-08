<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = ['id'];

    public const TYPES = [
        'bank_transfer' => 'Transferencia bancaria',
        'card' => 'Tarjeta de crédito/débito',
        'paypal' => 'PayPal',
        'cash' => 'Efectivo',
        'crypto' => 'Criptomonedas',
        'other' => 'Otro',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
