<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Company;

/**
 * Resuelve la empresa (tenant) y la sucursal activas para la petición actual.
 * Permite fijarlas manualmente desde comandos de consola, seeders y pruebas.
 */
class Tenant
{
    protected static ?int $companyId = null;

    protected static ?Company $company = null;

    protected static bool $forced = false;

    public static function set(?Company $company): void
    {
        static::$company = $company;
        static::$companyId = $company?->id;
        static::$forced = true;
    }

    public static function forget(): void
    {
        static::$company = null;
        static::$companyId = null;
        static::$forced = false;
    }

    public static function id(): ?int
    {
        if (static::$forced) {
            return static::$companyId;
        }

        return auth()->user()?->company_id;
    }

    public static function company(): ?Company
    {
        $id = static::id();

        if (! $id) {
            return null;
        }

        if (static::$company?->id !== $id) {
            static::$company = Company::find($id);
        }

        return static::$company;
    }

    /**
     * Sucursal seleccionada (null = todas las sucursales).
     */
    public static function branchId(): ?int
    {
        $user = auth()->user();

        if ($user?->branch_id) {
            return $user->branch_id;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        return session('branch_id');
    }

    public static function branch(): ?Branch
    {
        $id = static::branchId();

        return $id ? Branch::find($id) : null;
    }

    /**
     * Sucursal a usar al registrar operaciones: la seleccionada o la principal.
     */
    public static function workingBranchId(): ?int
    {
        return static::branchId()
            ?? Branch::where('is_main', true)->value('id')
            ?? Branch::value('id');
    }
}
