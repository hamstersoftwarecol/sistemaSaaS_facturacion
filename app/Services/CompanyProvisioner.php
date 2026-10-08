<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Role;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Da de alta una empresa nueva con su configuración inicial completa.
 */
class CompanyProvisioner
{
    public function __construct(protected SubscriptionService $subscriptions) {}

    /**
     * @param  array{company_name:string, name:string, email:string, password:string, currency?:string, currency_symbol?:string, country?:string}  $data
     */
    public function provision(array $data, ?Plan $plan = null, string $cycle = 'monthly'): User
    {
        return DB::transaction(function () use ($data, $plan, $cycle) {
            $company = Company::create([
                'name' => $data['company_name'],
                'slug' => $this->uniqueSlug($data['company_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'currency_symbol' => $data['currency_symbol'] ?? '$',
                'invoice_terms' => 'Pago a 30 días desde la fecha de emisión.',
                'invoice_notes' => 'Gracias por su confianza.',
                'invoice_footer' => 'Documento generado electrónicamente. Verifique su autenticidad escaneando el código QR.',
            ]);

            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => 'Sede principal',
                'code' => 'PRI',
                'is_main' => true,
                'email' => $data['email'],
            ]);

            $adminRole = null;
            foreach (config('permissions.default_roles') as $name => $definition) {
                $role = Role::create([
                    'company_id' => $company->id,
                    'name' => $name,
                    'description' => $definition['description'],
                    'permissions' => Permissions::expand($definition['permissions']),
                    'is_system' => $name === 'Administrador',
                ]);
                $adminRole ??= $role;
            }

            $user = User::create([
                'company_id' => $company->id,
                'role_id' => $adminRole->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_owner' => true,
                'email_verified_at' => now(),
            ]);

            $company->update(['owner_id' => $user->id]);

            foreach ([['Exento', 0, false], ['IVA general', 19, true], ['IVA reducido', 5, false]] as [$name, $rate, $default]) {
                TaxRate::create(['company_id' => $company->id, 'name' => $name, 'rate' => $rate, 'is_default' => $default]);
            }

            foreach (['product' => ['General', 'Servicios'], 'expense' => ['Alquiler', 'Servicios públicos', 'Nómina', 'Transporte', 'Papelería', 'Otros gastos'], 'income' => ['Intereses', 'Comisiones', 'Otros ingresos']] as $type => $names) {
                foreach ($names as $name) {
                    Category::create(['company_id' => $company->id, 'type' => $type, 'name' => $name]);
                }
            }

            Account::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'name' => 'Caja general', 'type' => 'cash']);

            $plan ??= Plan::active()->first();
            if ($plan) {
                $this->subscriptions->start($company, $plan, $cycle);
            }

            return $user;
        });
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'empresa';
        $slug = $base;
        $i = 2;
        while (Company::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
