<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Catálogos de configuración protegidos por los permisos "settings.view|edit".
 */
abstract class SettingsModuleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:settings.view', only: ['index', 'show']),
            new Middleware('can:settings.edit', except: ['index', 'show']),
        ];
    }
}
