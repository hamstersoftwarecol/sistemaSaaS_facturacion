<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Formula extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const APPLIES_TO = ['sales' => 'Documentos de venta', 'purchases' => 'Documentos de compra', 'all' => 'Todos'];

    public const ACTIONS = [
        'add' => 'Sumar al total (recargo)',
        'subtract' => 'Restar del total (retención / descuento)',
        'info' => 'Solo informativo',
    ];

    public const VARIABLES = [
        'subtotal' => 'Suma de líneas antes de impuestos (con descuentos)',
        'gross' => 'Suma de líneas antes de descuentos',
        'discount' => 'Total de descuentos de línea',
        'tax' => 'Total de impuestos',
        'total' => 'Subtotal + impuestos (antes de fórmulas)',
        'items' => 'Número de líneas',
        'quantity' => 'Suma de cantidades',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function appliesTo(string $documentType): bool
    {
        $group = in_array($documentType, ['purchase_order', 'debit_note']) ? 'purchases' : 'sales';

        return $this->applies_to === 'all' || $this->applies_to === $group;
    }
}
