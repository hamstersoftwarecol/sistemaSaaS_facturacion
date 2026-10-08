<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controlador base de los módulos de la empresa: aplica los permisos
 * "modulo.view|create|edit|delete" a las acciones REST estándar.
 */
abstract class ModuleController extends Controller implements HasMiddleware
{
    protected static string $module;

    /** Acciones adicionales => permiso requerido (view/create/edit/delete). */
    protected static array $extraActions = [];

    public static function middleware(): array
    {
        $module = static::$module;
        $map = [
            'view' => ['index', 'show'],
            'create' => ['create', 'store'],
            'edit' => ['edit', 'update'],
            'delete' => ['destroy'],
        ];

        foreach (static::$extraActions as $action => $ability) {
            $map[$ability][] = $action;
        }

        return collect($map)
            ->map(fn (array $actions, string $ability) => new Middleware("can:{$module}.{$ability}", only: $actions))
            ->values()
            ->all();
    }
}
