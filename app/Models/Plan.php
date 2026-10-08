<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'features' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return (float) $this->price_monthly <= 0 && (float) $this->price_yearly <= 0;
    }

    public function priceFor(string $cycle): float
    {
        return (float) ($cycle === 'yearly' ? $this->price_yearly : $this->price_monthly);
    }

    public function limitLabel(?int $value): string
    {
        return $value === null ? 'Ilimitado' : number_format($value);
    }

    /**
     * Texto del límite con concordancia: "1 usuario", "3 usuarios", "Usuarios ilimitados".
     */
    public function limitText(string $field, string $singular, string $plural, string $unlimited): string
    {
        $value = $this->{$field};

        if ($value === null) {
            return $unlimited;
        }

        return number_format($value, 0, ',', '.').' '.($value === 1 ? $singular : $plural);
    }

    public function limitLines(): array
    {
        return [
            $this->limitText('max_users', 'usuario', 'usuarios', 'Usuarios ilimitados'),
            $this->limitText('max_branches', 'sucursal', 'sucursales', 'Sucursales ilimitadas'),
            $this->limitText('max_invoices', 'factura al mes', 'facturas al mes', 'Facturas ilimitadas'),
            $this->limitText('max_products', 'producto', 'productos', 'Productos ilimitados'),
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price_monthly');
    }
}
