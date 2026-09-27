<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Unidades o Sub-actividades Productivas (ej. Agropecuaria -> Vacunos, Porcinos, Cuyes)
        if (!Schema::hasTable('productive_units')) {
            Schema::create('productive_units', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities')->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name', 150);
                $table->foreignId('in_charge_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['ACTIVA', 'INACTIVA', 'EN_MANTENIMIENTO'])->default('ACTIVA');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['productive_activity_id', 'code'], 'prod_unit_act_code_uniq');
            });
        }

        // 2. Categorías Jerárquicas de Ingresos y Gastos (ej. Insumos > Fertilizantes)
        if (!Schema::hasTable('activity_transaction_categories')) {
            Schema::create('activity_transaction_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('parent_id')->nullable()->constrained('activity_transaction_categories')->nullOnDelete();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->enum('type', ['INCOME', 'EXPENSE'])->index();
                $table->boolean('is_active')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Fuentes de Financiamiento / Fondos (Banco de la Nación, Coop. Tocache, Caja Chica)
        if (!Schema::hasTable('fund_sources')) {
            Schema::create('fund_sources', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('bank_name', 100)->nullable();
                $table->string('account_number', 80)->nullable();
                $table->string('currency', 3)->default('PEN');
                $table->decimal('initial_balance', 14, 2)->default(0);
                $table->decimal('current_balance', 14, 2)->default(0);
                $table->boolean('is_cut')->default(false); // Es Cuenta Única del Tesoro
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Transacciones Financieras de Ingresos y Gastos de Actividades (APE)
        if (!Schema::hasTable('activity_transactions')) {
            Schema::create('activity_transactions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('transaction_code', 60)->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->foreignId('productive_unit_id')->nullable()->constrained('productive_units')->nullOnDelete();
                $table->foreignId('category_id')->constrained('activity_transaction_categories');
                $table->foreignId('fund_source_id')->constrained('fund_sources');
                $table->enum('transaction_type', ['INCOME', 'EXPENSE'])->index();
                $table->decimal('amount', 14, 2);
                $table->date('transaction_date');
                $table->unsignedTinyInteger('period_month');
                $table->year('period_year');
                $table->string('voucher_type', 50)->nullable(); // FACTURA, BOLETA, RECIBO, DEPOSITO, DJ, OTRO
                $table->string('voucher_number', 80)->nullable();
                $table->text('concept');
                $table->string('beneficiary_or_payer', 180)->nullable();
                
                // Conexiones para evitar duplicidad con el núcleo del ERP
                $table->foreignId('cash_id')->nullable()->constrained('cashes')->nullOnDelete();
                $table->foreignId('buy_id')->nullable()->constrained('buys')->nullOnDelete();
                $table->foreignId('sale_note_id')->nullable()->constrained('sale_notes')->nullOnDelete();
                $table->foreignId('billing_id')->nullable()->constrained('billings')->nullOnDelete();

                $table->enum('status', ['REGISTERED', 'VERIFIED', 'ANULLED'])->default('REGISTERED');
                $table->foreignId('registered_by_user_id')->constrained('users');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['productive_activity_id', 'period_year', 'period_month'], 'act_trx_period_idx');
                $table->index(['fund_source_id', 'transaction_date'], 'act_trx_fund_idx');
            });
        }

        // 5. RDR: Transferencias a la Cuenta Única del Tesoro (CUT)
        if (!Schema::hasTable('rdr_cut_transfers')) {
            Schema::create('rdr_cut_transfers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('transfer_code', 60)->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->foreignId('source_fund_id')->constrained('fund_sources');
                $table->string('cut_account_number', 80);
                $table->decimal('amount', 14, 2);
                $table->date('transfer_date');
                $table->string('bank_operation_number', 80);
                $table->enum('status', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'EXECUTED', 'REJECTED'])->default('DRAFT');
                $table->foreignId('requested_by_user_id')->constrained('users');
                $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 6. RDR: Préstamos Internos / Habilitaciones de Fondos (ej. Viáticos/Comisión)
        if (!Schema::hasTable('rdr_internal_loans')) {
            Schema::create('rdr_internal_loans', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('loan_code', 60)->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->foreignId('source_fund_id')->constrained('fund_sources');
                $table->foreignId('beneficiary_user_id')->constrained('users');
                $table->string('loan_reason', 255);
                $table->decimal('amount_lent', 14, 2);
                $table->decimal('amount_repaid', 14, 2)->default(0);
                $table->date('issue_date');
                $table->date('due_date');
                $table->enum('status', ['PENDING', 'PARTIALLY_REPAID', 'FULLY_REPAID', 'DEFAULTED'])->default('PENDING');
                $table->string('repayment_reference', 100)->nullable();
                $table->foreignId('authorized_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 7. RDR: Conciliaciones Bancarias de Saldos Declarados
        if (!Schema::hasTable('rdr_bank_reconciliations')) {
            Schema::create('rdr_bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('fund_source_id')->constrained('fund_sources');
                $table->year('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->date('statement_closing_date');
                $table->decimal('bank_statement_balance', 14, 2);
                $table->decimal('system_calculated_balance', 14, 2);
                $table->decimal('reconciled_difference', 14, 2)->default(0);
                $table->enum('status', ['BALANCED', 'DISCREPANCY', 'IN_REVIEW'])->default('IN_REVIEW');
                $table->foreignId('reconciled_by_user_id')->constrained('users');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['fund_source_id', 'period_year', 'period_month'], 'rdr_reconciliation_unique');
            });
        }

        // 8. Cierres Mensuales y Anuales de Actividad (sujetos a document_approvals)
        if (!Schema::hasTable('activity_period_closures')) {
            Schema::create('activity_period_closures', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('closure_code', 60)->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->year('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->decimal('total_income', 14, 2)->default(0);
                $table->decimal('total_expense', 14, 2)->default(0);
                $table->decimal('net_balance', 14, 2)->default(0);
                $table->decimal('bn_balance', 14, 2)->default(0);
                $table->decimal('coop_balance', 14, 2)->default(0);
                $table->decimal('cash_balance', 14, 2)->default(0);
                $table->enum('approval_status', ['BORRADOR', 'EN_REVISION', 'APROBADO', 'OBSERVADO', 'RECHAZADO'])->default('BORRADOR');
                $table->foreignId('closed_by_user_id')->constrained('users');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['productive_activity_id', 'period_year', 'period_month'], 'act_closure_period_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_period_closures');
        Schema::dropIfExists('rdr_bank_reconciliations');
        Schema::dropIfExists('rdr_internal_loans');
        Schema::dropIfExists('rdr_cut_transfers');
        Schema::dropIfExists('activity_transactions');
        Schema::dropIfExists('fund_sources');
        Schema::dropIfExists('activity_transaction_categories');
        Schema::dropIfExists('productive_units');
    }
};
