<?php

namespace Tests\Concerns;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Role;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\CompanyProvisioner;
use App\Services\InventoryService;
use App\Support\Tenant;
use Database\Seeders\PlatformSeeder;

trait CreatesCompanies
{
    protected function createCompanyOwner(string $email = 'owner@test.com', string $plan = 'profesional'): User
    {
        if (! Plan::exists()) {
            $this->seed(PlatformSeeder::class);
        }

        $user = app(CompanyProvisioner::class)->provision([
            'company_name' => 'Empresa '.$email,
            'name' => 'Owner '.$email,
            'email' => $email,
            'password' => 'password',
        ], Plan::where('slug', $plan)->first());

        Tenant::forget();

        return $user->fresh();
    }

    protected function createUserWithRole(User $owner, string $role, string $email): User
    {
        return User::create([
            'company_id' => $owner->company_id,
            'role_id' => Role::withoutGlobalScopes()->where('company_id', $owner->company_id)->where('name', $role)->value('id'),
            'name' => $role,
            'email' => $email,
            'password' => 'password',
        ]);
    }

    /**
     * Ejecuta un callback en el contexto (tenant) de la empresa del usuario.
     */
    protected function asTenant(User $user, callable $callback)
    {
        Tenant::set($user->company);
        try {
            return $callback();
        } finally {
            Tenant::forget();
        }
    }

    protected function catalog(User $owner): array
    {
        return $this->asTenant($owner, function () {
            $tax = TaxRate::where('rate', 19)->first();
            $product = Product::create(['sku' => 'P-1', 'name' => 'Producto de prueba', 'cost_price' => 60, 'sale_price' => 100, 'tax_rate_id' => $tax->id, 'track_stock' => true]);
            $branchId = Branch::where('is_main', true)->value('id');
            app(InventoryService::class)->move($product, $branchId, 50, 'opening');

            return [
                'customer' => Customer::create(['name' => 'Cliente de prueba', 'payment_terms' => 30]),
                'product' => $product,
                'tax' => $tax,
                'account' => Account::first(),
                'branch_id' => $branchId,
            ];
        });
    }
}
