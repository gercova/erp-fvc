<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update accounting_periods to support CLOSED status and link closing/opening entries
        if (Schema::hasTable('accounting_periods')) {
            // Update enum in MySQL to include CLOSED
            DB::statement("ALTER TABLE accounting_periods MODIFY COLUMN status ENUM('OPEN', 'SOFT_CLOSED', 'CLOSED', 'LOCKED') NOT NULL DEFAULT 'OPEN'");

            Schema::table('accounting_periods', function (Blueprint $table) {
                if (!Schema::hasColumn('accounting_periods', 'closing_entry_id')) {
                    $table->foreignId('closing_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                }
                if (!Schema::hasColumn('accounting_periods', 'opening_entry_id')) {
                    $table->foreignId('opening_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                }
            });
        }

        // 2. Financial Statement Templates (Balance Sheet, Income Statement Nature, Income Statement Function)
        if (!Schema::hasTable('financial_statement_templates')) {
            Schema::create('financial_statement_templates', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->enum('statement_type', ['BALANCE_SHEET', 'INCOME_STATEMENT_NATURE', 'INCOME_STATEMENT_FUNCTION']);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Financial Statement Template Lines (hierarchical and account range mapping)
        if (!Schema::hasTable('financial_statement_template_lines')) {
            Schema::create('financial_statement_template_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('template_id')->constrained('financial_statement_templates')->cascadeOnDelete();
                $table->string('line_code', 50);
                $table->foreignId('parent_line_id')->nullable()->constrained('financial_statement_template_lines')->nullOnDelete();
                $table->string('line_name', 150);
                $table->enum('line_type', ['HEADER', 'ITEM', 'SUBTOTAL', 'TOTAL'])->default('ITEM');
                $table->string('account_range', 100)->nullable()->comment('Ej: 10, 12..14, 20-29, 60,61, 70*');
                $table->enum('polarity', ['ADD', 'SUBTRACT'])->default('ADD');
                $table->string('calculation_formula', 255)->nullable();
                $table->integer('sort_order')->default(0);
                $table->integer('level')->default(1);
                $table->boolean('is_bold')->default(false);
                $table->timestamps();

                $table->index(['template_id', 'sort_order']);
                $table->unique(['template_id', 'line_code'], 'fs_line_code_unique');
            });
        }

        // 4. Accounting Period Closures (Type 10 for DocumentApprovalService)
        if (!Schema::hasTable('accounting_period_closures')) {
            Schema::create('accounting_period_closures', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('accounting_period_id')->constrained('accounting_periods')->cascadeOnDelete();
                $table->enum('closure_type', ['MONTHLY', 'ANNUAL'])->default('MONTHLY');
                $table->enum('status', ['DRAFT', 'PENDING_APPROVAL', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('DRAFT');
                $table->decimal('net_result', 14, 2)->default(0);
                $table->decimal('total_assets', 14, 2)->default(0);
                $table->decimal('total_liabilities', 14, 2)->default(0);
                $table->decimal('total_equity', 14, 2)->default(0);
                $table->foreignId('closing_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('opening_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('closed_by_user_id')->constrained('users');
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['accounting_period_id', 'status']);
            });
        }

        // 5. Accounting Period Audit Logs (Audit trail for pre-close, close, lock, and audited reopening)
        if (!Schema::hasTable('accounting_period_audit_logs')) {
            Schema::create('accounting_period_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('accounting_period_id')->constrained('accounting_periods')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users');
                $table->string('action', 50); // CLOSE, LOCK, REOPEN, GENERATE_CLOSING_ENTRIES
                $table->string('from_status', 20)->nullable();
                $table->string('to_status', 20)->nullable();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['accounting_period_id', 'action']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_period_audit_logs');
        Schema::dropIfExists('accounting_period_closures');
        Schema::dropIfExists('financial_statement_template_lines');
        Schema::dropIfExists('financial_statement_templates');

        if (Schema::hasTable('accounting_periods')) {
            Schema::table('accounting_periods', function (Blueprint $table) {
                if (Schema::hasColumn('accounting_periods', 'opening_entry_id')) {
                    $table->dropForeign(['opening_entry_id']);
                    $table->dropColumn('opening_entry_id');
                }
                if (Schema::hasColumn('accounting_periods', 'closing_entry_id')) {
                    $table->dropForeign(['closing_entry_id']);
                    $table->dropColumn('closing_entry_id');
                }
            });
            DB::statement("ALTER TABLE accounting_periods MODIFY COLUMN status ENUM('OPEN', 'SOFT_CLOSED', 'LOCKED') NOT NULL DEFAULT 'OPEN'");
        }
    }
};
