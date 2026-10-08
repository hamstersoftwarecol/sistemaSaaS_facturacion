<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\TaxRate;
use App\Services\CompanyProvisioner;
use App\Services\DocumentService;
use App\Support\Tenant;
use Illuminate\Database\Seeder;

/**
 * Segunda empresa (plan Básico en prueba) para demostrar el aislamiento multi-tenant
 * y la aprobación de pagos desde el panel de administración.
 */
class SecondCompanySeeder extends Seeder
{
    public function run(CompanyProvisioner $provisioner, DocumentService $documents): void
    {
        $basic = Plan::where('slug', 'basico')->first();
        $owner = $provisioner->provision([
            'company_name' => 'Ferretería El Tornillo',
            'name' => 'Pedro Salinas',
            'email' => 'ferreteria@facturapro.test',
            'password' => 'password',
            'currency' => 'MXN',
            'currency_symbol' => '$',
            'country' => 'México',
        ], $basic);

        $company = $owner->company;
        $company->update(['invoice_style' => 'bold', 'primary_color' => '#e11d48', 'tax_id' => 'FET010203AB1', 'city' => 'Guadalajara']);
        Tenant::set($company);

        $iva = TaxRate::where('rate', 19)->first();
        $iva->update(['name' => 'IVA', 'rate' => 16]);
        $customer = Customer::create(['name' => 'Público en general', 'tax_id' => 'XAXX010101000']);
        $product = Product::create(['sku' => 'FER-001', 'name' => 'Martillo de uña 16 oz', 'cost_price' => 85, 'sale_price' => 149, 'tax_rate_id' => $iva->id, 'track_stock' => false]);

        $documents->save('invoice', [
            'customer_id' => $customer->id, 'date' => today()->toDateString(), 'due_date' => today()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 149, 'tax_rate_id' => $iva->id]],
        ]);

        SubscriptionPayment::create([
            'company_id' => $company->id, 'subscription_id' => $company->subscription->id, 'plan_id' => $basic->id,
            'payment_method_id' => 1, 'billing_cycle' => 'monthly', 'amount' => 19, 'reference' => 'SPEI-778899',
            'notes' => 'Pago del primer mes', 'status' => 'pending', 'paid_at' => now(),
        ]);

        Tenant::forget();
    }
}
