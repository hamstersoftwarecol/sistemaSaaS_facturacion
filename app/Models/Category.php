<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const TYPES = ['product' => 'Productos', 'expense' => 'Gastos', 'income' => 'Ingresos'];

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type)->orderBy('name');
    }
}
