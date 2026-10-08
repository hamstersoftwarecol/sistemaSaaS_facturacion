<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const TYPES = ['product' => 'Producto', 'service' => 'Servicio'];

    public const UNITS = ['und' => 'Unidad', 'kg' => 'Kilogramo', 'g' => 'Gramo', 'l' => 'Litro', 'm' => 'Metro', 'caja' => 'Caja', 'paq' => 'Paquete', 'hora' => 'Hora', 'serv' => 'Servicio'];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'min_stock' => 'decimal:3',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'custom_fields' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Existencias en una sucursal concreta o en todas (null).
     */
    public function stockIn(?int $branchId = null): float
    {
        if ($this->relationLoaded('stocks')) {
            $stocks = $this->stocks;

            return (float) ($branchId ? $stocks->where('branch_id', $branchId)->sum('quantity') : $stocks->sum('quantity'));
        }

        $query = $this->stocks();
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return (float) $query->sum('quantity');
    }

    public function currentStock(): float
    {
        return $this->stockIn(Tenant::branchId());
    }

    public function isLowStock(): bool
    {
        return $this->track_stock && $this->currentStock() <= (float) $this->min_stock;
    }

    public function priceForCustomer(?Customer $customer): float
    {
        if ($customer?->priceList && $customer->priceList->is_active) {
            return $customer->priceList->priceFor($this);
        }

        return (float) $this->sale_price;
    }

    public function barcodeValue(): string
    {
        return $this->barcode ?: $this->sku;
    }
}
