<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Document;
use App\Models\Expense;
use App\Models\Formula;
use App\Models\Income;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\Reconciliation;
use App\Models\RecurringInvoice;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\CompanyProvisioner;
use App\Services\DocumentService;
use App\Services\InventoryService;
use App\Services\LoanService;
use App\Support\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Empresa de demostración con datos realistas de los últimos meses.
 */
class DemoCompanySeeder extends Seeder
{
    protected DocumentService $documents;

    public function run(CompanyProvisioner $provisioner, DocumentService $documents, InventoryService $inventory, LoanService $loans): void
    {
        $this->documents = $documents;
        mt_srand(2026);

        $owner = $provisioner->provision([
            'company_name' => 'Distribuidora Andina S.A.S.',
            'name' => 'Laura Gómez',
            'email' => 'demo@facturapro.test',
            'password' => 'password',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'country' => 'Colombia',
        ], Plan::where('slug', 'profesional')->first());

        $company = $owner->company;
        $company->update([
            'legal_name' => 'Distribuidora Andina Sociedad por Acciones Simplificada',
            'tax_id' => '901.234.567-8',
            'phone' => '+57 601 555 0101',
            'website' => 'www.distribuidora-andina.test',
            'address' => 'Carrera 7 # 45-12, Oficina 301',
            'city' => 'Bogotá',
            'state' => 'Cundinamarca',
            'postal_code' => '110231',
            'invoice_style' => 'modern',
        ]);

        Tenant::set($company);
        Auth::setUser($owner);

        $subscription = Subscription::first();
        $subscription->update(['status' => 'active', 'billing_cycle' => 'yearly', 'price' => 490, 'trial_ends_at' => null, 'ends_at' => now()->addMonths(11)]);
        SubscriptionPayment::create([
            'company_id' => $company->id, 'subscription_id' => $subscription->id, 'plan_id' => $subscription->plan_id,
            'payment_method_id' => 1, 'billing_cycle' => 'yearly', 'amount' => 490, 'reference' => 'TRX-DEMO-0001',
            'status' => 'approved', 'paid_at' => now()->subMonth(),
        ]);

        // Sucursales, cuentas y usuarios
        $main = Branch::where('is_main', true)->first();
        $main->update(['name' => 'Sede Centro', 'code' => 'CEN', 'address' => 'Carrera 7 # 45-12', 'city' => 'Bogotá', 'phone' => '+57 601 555 0101']);
        $north = Branch::create(['name' => 'Sucursal Norte', 'code' => 'NOR', 'address' => 'Calle 140 # 19-30', 'city' => 'Bogotá', 'phone' => '+57 601 555 0202']);

        $cash = Account::where('type', 'cash')->first();
        $cash->update(['opening_balance' => 1500]);
        $cashNorth = Account::create(['branch_id' => $north->id, 'name' => 'Caja Sucursal Norte', 'type' => 'cash', 'opening_balance' => 500]);
        $bank = Account::create(['name' => 'Banco Nacional · Corriente', 'type' => 'bank', 'bank_name' => 'Banco Nacional', 'account_number' => '001-998877-66', 'opening_balance' => 25000]);
        $savings = Account::create(['name' => 'Banco del Pacífico · Ahorros', 'type' => 'bank', 'bank_name' => 'Banco del Pacífico', 'account_number' => '220-114433-01', 'opening_balance' => 12000]);

        $roles = Role::pluck('id', 'name');
        foreach ([
            ['Carlos Ramírez', 'vendedor@facturapro.test', 'Vendedor', $main->id],
            ['Ana Torres', 'contador@facturapro.test', 'Contador', null],
            ['Jorge Medina', 'almacen@facturapro.test', 'Almacén', $north->id],
        ] as [$name, $email, $role, $branchId]) {
            User::create(['company_id' => $company->id, 'name' => $name, 'email' => $email, 'password' => 'password', 'role_id' => $roles[$role], 'branch_id' => $branchId, 'email_verified_at' => now()]);
        }

        // Configuración: campos personalizados y fórmulas
        CustomField::create(['entity' => 'customer', 'label' => 'Vendedor asignado', 'name' => 'vendedor_asignado', 'type' => 'text']);
        CustomField::create(['entity' => 'customer', 'label' => 'Segmento', 'name' => 'segmento', 'type' => 'select', 'options' => ['Minorista', 'Mayorista', 'Corporativo'], 'show_on_document' => true]);
        CustomField::create(['entity' => 'product', 'label' => 'Garantía (meses)', 'name' => 'garantia_meses', 'type' => 'number']);
        CustomField::create(['entity' => 'document', 'label' => 'Orden de compra del cliente', 'name' => 'orden_compra_cliente', 'type' => 'text', 'show_on_document' => true]);

        Formula::create(['name' => 'Retención en la fuente (2,5 %)', 'code' => 'retefuente', 'expression' => 'round(subtotal * 0.025, 2)', 'applies_to' => 'purchases', 'action' => 'subtract', 'description' => 'Retención practicada a proveedores sobre compras.']);
        Formula::create(['name' => 'Recargo por envío', 'code' => 'envio', 'expression' => 'if(subtotal < 100, 8, 0)', 'applies_to' => 'sales', 'action' => 'add', 'is_active' => false, 'description' => 'Envío gratis para pedidos desde 100.']);

        // Catálogos
        $iva = TaxRate::where('rate', 19)->first();
        $reduced = TaxRate::where('rate', 5)->first();
        $exempt = TaxRate::where('rate', 0)->first();

        $categories = collect(['Papelería', 'Tecnología', 'Cafetería', 'Aseo'])->mapWithKeys(fn ($n) => [$n => Category::create(['type' => 'product', 'name' => $n])]);
        $servicesCategory = Category::where('type', 'product')->where('name', 'Servicios')->first();

        $wholesale = PriceList::create(['name' => 'Mayorista', 'description' => 'Distribuidores y compras por volumen', 'type' => 'percentage', 'percentage' => -12]);
        $vip = PriceList::create(['name' => 'Clientes VIP', 'description' => 'Descuento por fidelidad', 'type' => 'percentage', 'percentage' => -5]);

        $productData = [
            ['Resma papel carta 75 g', 'PAP-001', '7701234500011', 'Papelería', 3.10, 4.90, $iva, 'paq', 400],
            ['Bolígrafo tinta azul x12', 'PAP-002', '7701234500028', 'Papelería', 2.40, 4.20, $iva, 'caja', 250],
            ['Cuaderno argollado 100 h', 'PAP-003', '7701234500035', 'Papelería', 1.80, 3.50, $iva, 'und', 300],
            ['Carpeta de presentación', 'PAP-004', '7701234500042', 'Papelería', 0.60, 1.30, $iva, 'und', 500],
            ['Marcadores permanentes x4', 'PAP-005', '7701234500059', 'Papelería', 2.10, 3.90, $iva, 'paq', 180],
            ['Mouse inalámbrico', 'TEC-001', '7701234500066', 'Tecnología', 6.50, 12.90, $iva, 'und', 60],
            ['Teclado USB español', 'TEC-002', '7701234500073', 'Tecnología', 8.20, 15.50, $iva, 'und', 45],
            ['Memoria USB 64 GB', 'TEC-003', '7701234500080', 'Tecnología', 5.40, 9.90, $iva, 'und', 80],
            ['Audífonos con micrófono', 'TEC-004', '7701234500097', 'Tecnología', 9.80, 19.90, $iva, 'und', 35],
            ['Cable HDMI 2 m', 'TEC-005', '7701234500103', 'Tecnología', 2.30, 5.50, $iva, 'und', 90],
            ['Café molido 500 g', 'CAF-001', '7701234500110', 'Cafetería', 4.10, 6.80, $reduced, 'und', 120],
            ['Azúcar 1 kg', 'CAF-002', '7701234500127', 'Cafetería', 0.95, 1.60, $reduced, 'kg', 150],
            ['Vasos desechables x50', 'CAF-003', '7701234500134', 'Cafetería', 1.10, 2.20, $iva, 'paq', 200],
            ['Agua mineral 600 ml x24', 'CAF-004', '7701234500141', 'Cafetería', 5.20, 8.90, $exempt, 'caja', 70],
            ['Desinfectante multiusos 1 l', 'ASE-001', '7701234500158', 'Aseo', 1.40, 2.90, $iva, 'l', 140],
            ['Papel higiénico x12', 'ASE-002', '7701234500165', 'Aseo', 3.60, 6.50, $iva, 'paq', 110],
            ['Toallas de papel x3', 'ASE-003', '7701234500172', 'Aseo', 2.20, 4.10, $iva, 'paq', 2],
            ['Jabón líquido de manos', 'ASE-004', '7701234500189', 'Aseo', 1.70, 3.40, $iva, 'und', 3],
        ];

        $products = collect();
        foreach ($productData as [$name, $sku, $barcode, $cat, $cost, $price, $tax, $unit, $stock]) {
            $product = Product::create([
                'category_id' => $categories[$cat]->id, 'tax_rate_id' => $tax->id, 'type' => 'product', 'sku' => $sku, 'barcode' => $barcode,
                'name' => $name, 'unit' => $unit, 'cost_price' => $cost, 'sale_price' => $price, 'track_stock' => true,
                'min_stock' => max(10, (int) round($stock * 0.3)), 'custom_fields' => $cat === 'Tecnología' ? ['garantia_meses' => 12] : [],
            ]);
            $inventory->move($product, $main->id, $stock * 3, 'opening', ['notes' => 'Inventario inicial', 'date' => now()->subMonths(7)]);
            $inventory->move($product, $north->id, $stock * 2, 'opening', ['notes' => 'Inventario inicial', 'date' => now()->subMonths(7)]);
            $products->push($product);
        }

        foreach ([['Instalación y configuración de equipos', 'SRV-001', 25, 45], ['Soporte técnico (hora)', 'SRV-002', 12, 30], ['Mantenimiento preventivo mensual', 'SRV-003', 60, 120]] as [$name, $sku, $cost, $price]) {
            $products->push(Product::create([
                'category_id' => $servicesCategory?->id, 'tax_rate_id' => $iva->id, 'type' => 'service', 'sku' => $sku, 'name' => $name,
                'unit' => 'serv', 'cost_price' => $cost, 'sale_price' => $price, 'track_stock' => false,
            ]));
        }
        $wholesale->items()->create(['product_id' => $products[0]->id, 'price' => 4.10]);

        $customerData = [
            ['Consumidor final', '222222222222', null, null, 0, null, 'Minorista'],
            ['Colegio San Martín', '860.111.222-3', 'compras@colegiosanmartin.test', 'Bogotá', 30, $wholesale, 'Corporativo'],
            ['Oficinas Integrales Ltda.', '900.333.444-5', 'pagos@oficinasintegrales.test', 'Bogotá', 30, $vip, 'Corporativo'],
            ['Papelería El Estudiante', '79.555.666-1', 'elestudiante@correo.test', 'Chía', 15, $wholesale, 'Mayorista'],
            ['Clínica Santa Fe', '860.777.888-9', 'tesoreria@clinicasantafe.test', 'Bogotá', 45, null, 'Corporativo'],
            ['María Fernanda López', '1.020.345.678', 'mafe.lopez@correo.test', 'Bogotá', 0, null, 'Minorista'],
            ['Constructora Horizonte S.A.', '900.999.000-2', 'contabilidad@horizonte.test', 'Medellín', 60, $vip, 'Corporativo'],
            ['Café Aroma Andino', '901.222.111-0', 'gerencia@aromaandino.test', 'Bogotá', 15, null, 'Mayorista'],
            ['Universidad del Valle Verde', '890.123.456-7', 'proveedores@uvv.test', 'Cali', 30, $wholesale, 'Corporativo'],
            ['Tienda La Esquina', '52.888.999-4', 'laesquina@correo.test', 'Soacha', 8, null, 'Minorista'],
        ];
        $customers = collect();
        foreach ($customerData as $i => [$name, $taxId, $email, $city, $terms, $list, $segment]) {
            $customers->push(Customer::create([
                'code' => 'CL'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT), 'name' => $name, 'tax_id' => $taxId, 'email' => $email,
                'phone' => '+57 3'.mt_rand(10, 22).' '.mt_rand(100, 999).' '.mt_rand(1000, 9999), 'address' => 'Calle '.mt_rand(10, 150).' # '.mt_rand(1, 99).'-'.mt_rand(1, 99),
                'city' => $city, 'country' => 'Colombia', 'payment_terms' => $terms, 'price_list_id' => $list?->id,
                'credit_limit' => $terms ? 5000 : 0, 'custom_fields' => ['segmento' => $segment, 'vendedor_asignado' => $i % 2 ? 'Carlos Ramírez' : 'Laura Gómez'],
            ]));
        }

        $suppliers = collect();
        foreach ([
            ['Papeles y Cartones del Norte S.A.', '800.123.456-1', 'Andrés Rojas'],
            ['TecnoImport Colombia', '900.654.321-9', 'Diana Pérez'],
            ['Café de la Montaña', '901.888.777-3', 'Luis Herrera'],
            ['Químicos y Aseo Industrial', '830.222.333-5', 'Paola Castro'],
        ] as [$name, $taxId, $contact]) {
            $suppliers->push(Supplier::create([
                'name' => $name, 'tax_id' => $taxId, 'contact_name' => $contact, 'email' => strtolower(explode(' ', $contact)[0]).'@proveedor.test',
                'phone' => '+57 601 '.mt_rand(200, 899).' '.mt_rand(1000, 9999), 'city' => 'Bogotá', 'country' => 'Colombia', 'payment_terms' => 30,
            ]));
        }

        // Los dos últimos productos de aseo quedan con poco stock para mostrar las alertas.
        $goods = $products->where('type', 'product')->filter(fn ($p) => ! in_array($p->sku, ['ASE-003', 'ASE-004']))->values();
        $services = $products->where('type', 'service')->values();
        $lines = function (int $count, ?Customer $customer = null, bool $purchase = false, ?int $branchId = null) use ($goods, $services) {
            $pool = $purchase ? $goods : $goods->concat($services);
            $result = [];

            foreach ($pool->shuffle() as $p) {
                if (count($result) >= $count) {
                    break;
                }
                $quantity = $p->type === 'service' ? mt_rand(1, 4) : ($purchase ? mt_rand(60, 160) : mt_rand(6, 40));
                if (! $purchase && $p->track_stock && $branchId) {
                    // Nunca vender más de lo disponible para que el inventario demo quede consistente.
                    $quantity = min($quantity, (int) floor($p->stockIn($branchId)));
                    if ($quantity < 1) {
                        continue;
                    }
                }
                $result[] = [
                    'product_id' => $p->id,
                    'description' => $p->name,
                    'quantity' => $quantity,
                    'unit_price' => $purchase ? (float) $p->cost_price : $p->priceForCustomer($customer?->load('priceList')),
                    'discount_percent' => ! $purchase && mt_rand(1, 6) === 1 ? 5 : 0,
                    'tax_rate_id' => $p->tax_rate_id,
                ];
            }

            return $result;
        };

        // Compras de los últimos meses (todas recibidas, con pagos)
        foreach (range(6, 1) as $monthsAgo) {
            $date = now()->subMonths($monthsAgo)->startOfMonth()->addDays(mt_rand(1, 6));
            $supplier = $suppliers->random();
            $po = $this->documents->save('purchase_order', [
                'supplier_id' => $supplier->id, 'branch_id' => $monthsAgo % 2 ? $main->id : $north->id, 'date' => $date->toDateString(), 'due_date' => $date->copy()->addDays(30)->toDateString(),
                'status' => 'received', 'items' => $lines(mt_rand(5, 8), null, true), 'notes' => 'Entregar en bodega.',
            ]);
            if ($monthsAgo > 1) {
                $this->payment('out', ['supplier_id' => $supplier->id, 'document_id' => $po->id, 'account_id' => $bank->id, 'method' => 'bank_transfer', 'amount' => $po->total, 'date' => $date->copy()->addDays(20)]);
            }
        }
        $pendingPo = $this->documents->save('purchase_order', [
            'supplier_id' => $suppliers[1]->id, 'branch_id' => $north->id, 'date' => today()->subDays(3)->toDateString(), 'due_date' => today()->addDays(27)->toDateString(),
            'status' => 'ordered', 'items' => $lines(3, null, true),
        ]);
        $receivedPo = Document::ofType('purchase_order')->where('status', 'received')->latest('date')->first()->load('items');
        $returned = $receivedPo->items->first();
        $this->documents->save('debit_note', [
            'supplier_id' => $receivedPo->supplier_id, 'parent_id' => $receivedPo->id, 'branch_id' => $receivedPo->branch_id,
            'reference' => $receivedPo->number, 'date' => today()->subDays(6)->toDateString(), 'notes' => 'Devolución por producto defectuoso.',
            'items' => [['product_id' => $returned->product_id, 'description' => $returned->description, 'quantity' => 5, 'unit_price' => $returned->unit_price, 'tax_rate_id' => $returned->tax_rate_id]],
        ]);

        // Ventas: presupuestos, facturas, cobros
        $invoiceCount = 0;
        foreach (range(6, 0) as $monthsAgo) {
            $perMonth = $monthsAgo === 0 ? mt_rand(5, 8) : mt_rand(10, 14);
            for ($i = 0; $i < $perMonth; $i++) {
                $date = $monthsAgo === 0
                    ? today()->subDays(mt_rand(0, max(0, today()->day - 1)))
                    : now()->subMonths($monthsAgo)->startOfMonth()->addDays(mt_rand(0, 26));
                $customer = $customers->random();
                $branch = mt_rand(1, 3) === 1 ? $north : $main;

                $invoice = $this->documents->save('invoice', [
                    'customer_id' => $customer->id, 'branch_id' => $branch->id, 'price_list_id' => $customer->price_list_id,
                    'date' => $date->toDateString(), 'due_date' => $date->copy()->addDays($customer->payment_terms ?: 0)->toDateString(),
                    'notes' => $company->invoice_notes, 'terms' => $company->invoice_terms,
                    'custom_fields' => mt_rand(1, 3) === 1 ? ['orden_compra_cliente' => 'OC-'.mt_rand(1000, 9999)] : [],
                    'items' => $lines(mt_rand(2, 5), $customer, false, $branch->id),
                ]);
                $invoiceCount++;

                $roll = mt_rand(1, 10);
                $account = $branch->id === $north->id ? $cashNorth : (mt_rand(0, 1) ? $cash : $bank);
                if ($monthsAgo >= 2 ? $roll <= 9 : $roll <= 5) {
                    $this->payment('in', ['customer_id' => $customer->id, 'document_id' => $invoice->id, 'account_id' => $account->id, 'method' => $account->type === 'bank' ? 'bank_transfer' : 'cash', 'amount' => $invoice->total, 'date' => $date->copy()->addDays(mt_rand(0, 20))->min(today()), 'branch_id' => $branch->id]);
                } elseif ($roll <= 7) {
                    $this->payment('in', ['customer_id' => $customer->id, 'document_id' => $invoice->id, 'account_id' => $bank->id, 'method' => 'bank_transfer', 'amount' => round($invoice->total * 0.5, 2), 'date' => $date->copy()->addDays(5)->min(today()), 'branch_id' => $branch->id]);
                }
            }
        }

        $paidInvoice = Document::ofType('invoice')->where('status', 'paid')->oldest('date')->first();
        $item = $paidInvoice->items()->whereNotNull('product_id')->first() ?? $paidInvoice->items()->first();
        $this->documents->save('credit_note', [
            'customer_id' => $paidInvoice->customer_id, 'parent_id' => $paidInvoice->id, 'branch_id' => $paidInvoice->branch_id,
            'reference' => $paidInvoice->number, 'date' => $paidInvoice->date->copy()->addDays(3)->toDateString(), 'notes' => 'Devolución parcial de mercancía.',
            'items' => [['product_id' => $item->product_id, 'description' => $item->description, 'quantity' => min(2, (float) $item->quantity), 'unit_price' => $item->unit_price, 'tax_rate_id' => $item->tax_rate_id]],
        ]);

        foreach (['draft', 'sent', 'accepted', 'rejected', 'sent'] as $i => $status) {
            $customer = $customers[$i + 1];
            $date = today()->subDays(mt_rand(1, 40));
            $quote = $this->documents->save('quote', [
                'customer_id' => $customer->id, 'branch_id' => $main->id, 'price_list_id' => $customer->price_list_id, 'status' => $status,
                'date' => $date->toDateString(), 'due_date' => $date->copy()->addDays(15)->toDateString(),
                'terms' => 'Oferta válida por 15 días. Precios sujetos a disponibilidad.', 'items' => $lines(mt_rand(2, 5), $customer, false, $main->id),
            ]);
            if ($status === 'accepted') {
                $this->documents->convert($quote->load('items'), 'invoice');
            }
        }

        $delivery = $this->documents->save('delivery_note', [
            'customer_id' => $customers[4]->id, 'branch_id' => $main->id, 'date' => today()->subDays(2)->toDateString(), 'status' => 'delivered',
            'notes' => 'Entregado en recepción a nombre de la Dra. Salazar.', 'items' => $lines(3, $customers[4], false, $main->id),
        ]);
        $this->documents->save('delivery_note', [
            'customer_id' => $customers[8]->id, 'branch_id' => $north->id, 'date' => today()->toDateString(), 'status' => 'pending',
            'items' => $lines(2, $customers[8], false, $north->id),
        ]);

        // Factura recurrente
        RecurringInvoice::create([
            'branch_id' => $main->id, 'customer_id' => $customers[2]->id, 'title' => 'Mantenimiento preventivo mensual',
            'frequency' => 'monthly', 'interval' => 1, 'start_date' => today()->addDays(5), 'next_date' => today()->addDays(5),
            'due_days' => 30, 'status' => 'active', 'total' => 142.80,
            'items' => [['product_id' => $services[2]->id, 'description' => 'Mantenimiento preventivo mensual de equipos', 'quantity' => 1, 'unit_price' => 120, 'discount_percent' => 0, 'tax_rate_id' => $iva->id]],
            'terms' => $company->invoice_terms,
        ]);

        // Gastos y otros ingresos
        $expenseCats = Category::where('type', 'expense')->pluck('id', 'name');
        foreach (range(6, 0) as $monthsAgo) {
            $month = now()->subMonths($monthsAgo)->startOfMonth();
            if ($monthsAgo === 0 && today()->day < 5) {
                continue;
            }
            Expense::create(['branch_id' => $main->id, 'category_id' => $expenseCats['Alquiler'], 'account_id' => $bank->id, 'date' => $month->copy()->addDays(4)->min(today()), 'amount' => 650, 'description' => 'Arriendo local Sede Centro', 'reference' => 'ARR-'.$month->format('Ym')]);
            Expense::create(['branch_id' => $north->id, 'category_id' => $expenseCats['Alquiler'], 'account_id' => $bank->id, 'date' => $month->copy()->addDays(4)->min(today()), 'amount' => 350, 'description' => 'Arriendo local Sucursal Norte']);
            Expense::create(['branch_id' => $main->id, 'category_id' => $expenseCats['Servicios públicos'], 'account_id' => $cash->id, 'date' => $month->copy()->addDays(9)->min(today()), 'amount' => mt_rand(120, 180), 'tax_amount' => 19, 'description' => 'Energía, agua e internet']);
            if ($monthsAgo > 0) {
                Expense::create(['branch_id' => $main->id, 'category_id' => $expenseCats['Nómina'], 'account_id' => $bank->id, 'date' => $month->copy()->endOfMonth(), 'amount' => 1100, 'description' => 'Nómina auxiliar de ventas']);
            }
            Expense::create(['branch_id' => $main->id, 'category_id' => $expenseCats['Transporte'], 'supplier_id' => null, 'account_id' => $cash->id, 'date' => $month->copy()->addDays(mt_rand(10, 20))->min(today()), 'amount' => mt_rand(60, 140), 'description' => 'Fletes y domicilios']);
        }
        $incomeCats = Category::where('type', 'income')->pluck('id', 'name');
        foreach (range(5, 1) as $monthsAgo) {
            Income::create(['branch_id' => $main->id, 'category_id' => $incomeCats['Intereses'], 'account_id' => $savings->id, 'date' => now()->subMonths($monthsAgo)->endOfMonth(), 'amount' => mt_rand(35, 60), 'description' => 'Rendimientos cuenta de ahorros']);
        }
        Income::create(['branch_id' => $main->id, 'category_id' => $incomeCats['Comisiones'], 'account_id' => $bank->id, 'date' => today()->subDays(12), 'amount' => 320, 'description' => 'Comisión por intermediación comercial']);

        // Movimientos de tesorería
        $bank->transactions()->create(['type' => 'transfer', 'direction' => 'in', 'amount' => 2000, 'date' => today()->subDays(15), 'description' => 'Consignación de caja al banco', 'reference' => 'TRF-DEMO1']);
        $cash->transactions()->create(['type' => 'transfer', 'direction' => 'out', 'amount' => 2000, 'date' => today()->subDays(15), 'description' => 'Consignación de caja al banco', 'reference' => 'TRF-DEMO1']);

        Cheque::create(['branch_id' => $main->id, 'type' => 'received', 'cheque_number' => '0045871', 'bank_name' => 'Banco de Occidente', 'customer_id' => $customers[4]->id, 'account_id' => $bank->id, 'amount' => 850, 'issue_date' => today()->subDays(10), 'due_date' => today()->addDays(5), 'status' => 'pending']);
        Cheque::create(['branch_id' => $main->id, 'type' => 'received', 'cheque_number' => '0012209', 'bank_name' => 'Bancolombia', 'customer_id' => $customers[6]->id, 'account_id' => $bank->id, 'amount' => 1240, 'issue_date' => today()->subDays(40), 'due_date' => today()->subDays(20), 'status' => 'cleared', 'cleared_at' => today()->subDays(19)]);
        Cheque::create(['branch_id' => $main->id, 'type' => 'issued', 'cheque_number' => '1000321', 'bank_name' => 'Banco Nacional', 'supplier_id' => $suppliers[0]->id, 'account_id' => $bank->id, 'amount' => 600, 'issue_date' => today()->subDays(3), 'due_date' => today()->addDays(25), 'status' => 'pending']);

        $loan = Loan::create(['account_id' => $bank->id, 'type' => 'borrowed', 'party_name' => 'Banco Nacional', 'reference' => 'CRÉDITO 55-8812', 'principal' => 15000, 'interest_rate' => 14.5, 'installments_count' => 12, 'frequency' => 'monthly', 'start_date' => now()->subMonths(4)->startOfMonth(), 'status' => 'active', 'notes' => 'Crédito de capital de trabajo.']);
        $loans->regenerate($loan);
        foreach ($loan->installments()->where('due_date', '<', today())->get() as $installment) {
            $installment->update(['status' => 'paid', 'account_id' => $bank->id, 'paid_at' => $installment->due_date]);
        }

        // Conciliación bancaria de los movimientos de hace más de dos meses.
        $cutoff = now()->subMonths(2)->endOfMonth();
        $toReconcile = $bank->transactions()->whereDate('date', '<=', $cutoff)->get();
        $cleared = round((float) $bank->opening_balance + $toReconcile->sum(fn ($t) => $t->signedAmount()), 2);
        $reconciliation = Reconciliation::create([
            'account_id' => $bank->id, 'user_id' => $owner->id, 'statement_date' => $cutoff, 'statement_balance' => $cleared,
            'opening_balance' => $bank->opening_balance, 'cleared_balance' => $cleared, 'difference' => 0,
            'transactions_count' => $toReconcile->count(), 'notes' => 'Extracto mensual conciliado sin diferencias.',
        ]);
        $bank->transactions()->whereIn('id', $toReconcile->pluck('id'))->update(['reconciliation_id' => $reconciliation->id, 'reconciled_at' => $cutoff]);

        Tenant::forget();
        Auth::forgetUser();
    }

    protected function payment(string $direction, array $data): Payment
    {
        $sequence = (int) Payment::where('direction', $direction)->max('sequence') + 1;
        $payment = Payment::create($data + [
            'direction' => $direction,
            'sequence' => $sequence,
            'number' => ($direction === 'in' ? 'RC-' : 'PP-').str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
        ]);

        if ($payment->document) {
            $this->documents->refreshBalance($payment->document);
        }

        return $payment;
    }
}
