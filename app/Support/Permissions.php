<?php

namespace App\Support;

class Permissions
{
    /**
     * Módulos indexados por clave: ['invoices' => ['label' => ..., 'actions' => [...]], ...].
     */
    public static function modules(): array
    {
        return collect(config('permissions.groups'))->collapse()->all();
    }

    /**
     * Lista plana de todas las claves de permiso.
     */
    public static function all(): array
    {
        $keys = [];
        foreach (static::modules() as $module => $definition) {
            foreach (array_keys($definition['actions']) as $action) {
                $keys[] = "{$module}.{$action}";
            }
        }

        return $keys;
    }

    /**
     * Expande comodines ("*", "modulo.*") a claves concretas.
     */
    public static function expand(array $patterns): array
    {
        $all = static::all();
        $result = [];

        foreach ($patterns as $pattern) {
            if ($pattern === '*') {
                return $all;
            }
            if (str_ends_with($pattern, '.*')) {
                $prefix = substr($pattern, 0, -1);
                $result = array_merge($result, array_filter($all, fn ($key) => str_starts_with($key, $prefix)));
            } elseif (in_array($pattern, $all, true)) {
                $result[] = $pattern;
            }
        }

        return array_values(array_unique($result));
    }

    public static function isKnown(string $ability): bool
    {
        return in_array($ability, static::all(), true);
    }
}
