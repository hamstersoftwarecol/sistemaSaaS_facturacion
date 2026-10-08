<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:2', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function label(): string
    {
        return $this->name.' ('.rtrim(rtrim(number_format((float) $this->rate, 2, '.', ''), '0'), '.').'%)';
    }
}
