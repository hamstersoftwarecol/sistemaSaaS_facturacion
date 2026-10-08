<?php

namespace App\Providers;

use App\Models\Branch;
use App\Support\Permissions;
use App\Support\Tenant;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        // Los permisos "modulo.accion" se resuelven contra el rol del usuario.
        Gate::before(function ($user, string $ability) {
            if (str_contains($ability, '.') && Permissions::isKnown($ability)) {
                return $user->hasPermission($ability);
            }

            return null;
        });

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with([
                'currentCompany' => Tenant::company(),
                'currentBranch' => Tenant::branch(),
                'availableBranches' => $user && ! $user->branch_id
                    ? Branch::where('is_active', true)->orderByDesc('is_main')->orderBy('name')->get()
                    : collect(),
            ]);
        });
    }
}
