<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos comerciales (presupuestos, facturas, albaranes, notas de crédito,
 * órdenes de compra y notas de débito) y facturas recurrentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->string('frequency')->default('monthly');
            $table->unsignedInteger('interval')->default(1);
            $table->date('start_date');
            $table->date('next_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('occurrences')->default(0);
            $table->unsignedInteger('due_days')->default(30);
            $table->string('status')->default('active');
            $table->json('items');
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('recurring_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('price_list_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('number');
            $table->string('reference')->nullable();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('adjustments_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->json('adjustments')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->json('custom_fields')->nullable();
            $table->boolean('affects_stock')->default(true);
            $table->boolean('stock_applied')->default(false);
            $table->string('verification_code', 40)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'type', 'sequence']);
            $table->index(['company_id', 'type', 'date']);
        });

        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 15, 3)->default(1);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_items');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('recurring_invoices');
    }
};
