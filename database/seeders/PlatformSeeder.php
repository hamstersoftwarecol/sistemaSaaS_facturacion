<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos base de la plataforma SaaS: planes, métodos de pago y super administrador.
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Gratis', 'slug' => 'gratis', 'description' => 'Para emprendedores que empiezan a facturar.', 'price_monthly' => 0, 'price_yearly' => 0, 'trial_days' => 0,
                'max_users' => 1, 'max_branches' => 1, 'max_invoices' => 30, 'max_products' => 50, 'sort_order' => 1,
                'features' => ['5 diseños de factura con QR', 'Clientes y productos', 'Pagos e informes básicos']],
            ['name' => 'Básico', 'slug' => 'basico', 'description' => 'Pequeños negocios con un punto de venta.', 'price_monthly' => 19, 'price_yearly' => 190, 'trial_days' => 14,
                'max_users' => 3, 'max_branches' => 1, 'max_invoices' => 300, 'max_products' => 500, 'sort_order' => 2,
                'features' => ['Todo lo del plan Gratis', 'Inventario y código de barras', 'Venta rápida con teclado', 'Efectivo y bancos']],
            ['name' => 'Profesional', 'slug' => 'profesional', 'description' => 'Empresas en crecimiento con varias sucursales.', 'price_monthly' => 49, 'price_yearly' => 490, 'trial_days' => 14,
                'max_users' => 10, 'max_branches' => 5, 'max_invoices' => null, 'max_products' => null, 'sort_order' => 3, 'is_featured' => true,
                'features' => ['Todo lo del plan Básico', 'Multi-sucursal y transferencias', 'Roles y permisos', 'Cheques, conciliación y préstamos', 'Campos personalizados y fórmulas']],
            ['name' => 'Empresarial', 'slug' => 'empresarial', 'description' => 'Cadenas y empresas con operación compleja.', 'price_monthly' => 99, 'price_yearly' => 990, 'trial_days' => 30,
                'max_users' => null, 'max_branches' => null, 'max_invoices' => null, 'max_products' => null, 'sort_order' => 4,
                'features' => ['Todo lo del plan Profesional', 'Usuarios y sucursales ilimitados', 'Soporte prioritario', 'Acompañamiento en la implementación']],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $methods = [
            ['name' => 'Transferencia bancaria', 'type' => 'bank_transfer', 'sort_order' => 1,
                'instructions' => "Banco Nacional · Cuenta corriente 001-234567-89\nTitular: FacturaPro SaaS S.A.S. · NIT 900.000.000-1\nIndique el nombre de su empresa en la referencia."],
            ['name' => 'PayPal', 'type' => 'paypal', 'sort_order' => 2, 'instructions' => 'Envíe el pago a pagos@facturapro.test y copie el ID de la transacción.'],
            ['name' => 'Tarjeta de crédito', 'type' => 'card', 'sort_order' => 3, 'instructions' => 'Pague con su tarjeta en el enlace seguro que le enviaremos por correo e indique el número de autorización.'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['name' => $method['name']], $method);
        }

        User::updateOrCreate(['email' => 'admin@facturapro.test'], [
            'name' => 'Administrador de la plataforma',
            'password' => 'password',
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);
    }
}
