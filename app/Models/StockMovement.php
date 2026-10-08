<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const TYPES = [
        'opening' => ['Inventario inicial', 'blue'],
        'sale' => ['Venta', 'red'],
        'purchase' => ['Compra', 'green'],
        'return_in' => ['Devolución de cliente', 'green'],
        'return_out' => ['Devolución a proveedor', 'red'],
        'adjustment' => ['Ajuste', 'yellow'],
        'transfer_in' => ['Transferencia entrante', 'indigo'],
        'transfer_out' => ['Transferencia saliente', 'indigo'],
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'cost' => 'decimal:2', 'date' => 'date'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function typeColor(): string
    {
        return self::TYPES[$this->type][1] ?? 'gray';
    }
}
