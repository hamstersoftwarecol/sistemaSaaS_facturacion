<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceList extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const TYPES = [
        'percentage' => 'Ajuste porcentual sobre precio base',
        'fixed' => 'Precios fijos por producto',
    ];

    protected function casts(): array
    {
        return ['percentage' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Precio de un producto según esta lista.
     */
    public function priceFor(Product $product): float
    {
        $fixed = $this->relationLoaded('items')
            ? $this->items->firstWhere('product_id', $product->id)
            : $this->items()->where('product_id', $product->id)->first();

        if ($fixed) {
            return (float) $fixed->price;
        }

        return round((float) $product->sale_price * (1 + (float) $this->percentage / 100), 2);
    }
}
