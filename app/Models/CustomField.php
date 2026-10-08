<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CustomField extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id'];

    public const ENTITIES = [
        'customer' => 'Clientes',
        'supplier' => 'Proveedores',
        'product' => 'Productos',
        'document' => 'Documentos (facturas, presupuestos...)',
    ];

    public const TYPES = [
        'text' => 'Texto',
        'textarea' => 'Texto largo',
        'number' => 'Número',
        'date' => 'Fecha',
        'select' => 'Lista desplegable',
        'checkbox' => 'Casilla (sí/no)',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'show_on_document' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function for(string $entity)
    {
        return static::where('entity', $entity)->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * Reglas de validación para los campos de una entidad.
     */
    public static function rulesFor(string $entity): array
    {
        $rules = [];
        foreach (static::for($entity) as $field) {
            $rule = [$field->is_required ? 'required' : 'nullable'];
            $rule[] = match ($field->type) {
                'number' => 'numeric',
                'date' => 'date',
                'checkbox' => 'boolean',
                default => 'string',
            };
            if ($field->type === 'select' && $field->options) {
                $rule[] = 'in:'.implode(',', $field->options);
            }
            $rules['custom_fields.'.$field->name] = $rule;
        }

        return $rules;
    }

    /**
     * Valores enviados en el formulario, limitados a los campos definidos para la entidad.
     */
    public static function valuesFrom(string $entity, Request $request): array
    {
        $input = (array) $request->input('custom_fields', []);

        return static::for($entity)->mapWithKeys(function (CustomField $field) use ($input) {
            $value = $input[$field->name] ?? null;

            return [$field->name => $field->type === 'checkbox' ? (bool) $value : (is_scalar($value) ? (string) $value : null)];
        })->filter(fn ($value) => $value !== null && $value !== '')->all();
    }

    public static function attributesFor(string $entity): array
    {
        return static::for($entity)->mapWithKeys(fn ($f) => ['custom_fields.'.$f->name => $f->label])->all();
    }

    public function displayValue($value): string
    {
        if ($this->type === 'checkbox') {
            return $value ? 'Sí' : 'No';
        }

        return (string) $value;
    }
}
