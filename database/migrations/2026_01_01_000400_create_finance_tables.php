<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finanzas: cuentas de efectivo y bancos, movimientos, pagos, gastos,
 * otros ingresos, cheques, conciliaciones bancarias y préstamos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('cash');
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('statement_date');
            $table->decimal('statement_balance', 15, 2);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('cleared_balance', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->unsignedInteger('transactions_count')->default(0);
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('date');
            $table->string('type');
            $table->string('direction', 3);
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->nullableMorphs('source');
            $table->foreignId('reconciliation_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
            $table->index(['account_id', 'date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('direction', 3)->default('in');
            $table->unsignedInteger('sequence');
            $table->string('number');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method')->default('cash');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('reference')->nullable();
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('reference')->nullable();
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type')->default('received');
            $table->string('cheque_number');
            $table->string('bank_name')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('party_name')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status')->default('pending');
            $table->timestamp('cleared_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('borrowed');
            $table->string('party_name');
            $table->string('reference')->nullable();
            $table->decimal('principal', 15, 2);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->unsignedInteger('installments_count');
            $table->string('frequency')->default('monthly');
            $table->date('start_date');
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->date('due_date');
            $table->decimal('principal', 15, 2);
            $table->decimal('interest', 15, 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('cheques');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('account_transactions');
        Schema::dropIfExists('reconciliations');
        Schema::dropIfExists('accounts');
    }
};
