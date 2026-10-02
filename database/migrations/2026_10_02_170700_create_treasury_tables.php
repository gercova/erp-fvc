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
        // 1. bank_accounts
        if (!Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('fund_source_id')->unique()->constrained('fund_sources')->restrictOnDelete();
                $table->string('account_number', 50);
                $table->string('cci', 50)->nullable();
                $table->string('bank_name', 100);
                $table->string('account_type', 30)->default('CURRENT'); // CURRENT, SAVINGS, CUT, COLLECTION
                $table->string('currency', 3)->default('PEN');
                $table->foreignId('accounting_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
                $table->decimal('initial_balance', 18, 2)->default(0.00);
                $table->decimal('current_balance', 18, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['bank_name', 'is_active']);
            });
        }

        // 2. bank_movements (with deduplication on date + reference + amount per bank account)
        if (!Schema::hasTable('bank_movements')) {
            Schema::create('bank_movements', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
                $table->date('movement_date');
                $table->string('operation_number', 60); // Reference / Voucher
                $table->string('movement_type', 10); // INFLOW, OUTFLOW
                $table->decimal('amount', 18, 2);
                $table->string('concept', 255);
                $table->string('reconciliation_status', 30)->default('PENDING'); // PENDING, MATCHED, RECONCILED, DISCREPANCY
                $table->foreignId('matched_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->string('import_batch_id', 60)->nullable();
                $table->timestamps();

                $table->unique(['bank_account_id', 'movement_date', 'operation_number', 'amount'], 'bank_mov_dedup_unique');
                $table->index(['bank_account_id', 'movement_date', 'reconciliation_status'], 'bank_mov_search_idx');
            });
        }

        // 3. Extend rdr_bank_reconciliations incrementally
        Schema::table('rdr_bank_reconciliations', function (Blueprint $table) {
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('fund_source_id')->constrained('bank_accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'accounting_period_id')) {
                $table->foreignId('accounting_period_id')->nullable()->after('bank_account_id')->constrained('accounting_periods')->nullOnDelete();
            }
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'book_calculated_balance')) {
                $table->decimal('book_calculated_balance', 18, 2)->default(0.00)->after('system_calculated_balance');
            }
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'uncredited_deposits')) {
                $table->decimal('uncredited_deposits', 18, 2)->default(0.00)->after('book_calculated_balance');
            }
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'outstanding_checks')) {
                $table->decimal('outstanding_checks', 18, 2)->default(0.00)->after('uncredited_deposits');
            }
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'unrecorded_bank_charges')) {
                $table->decimal('unrecorded_bank_charges', 18, 2)->default(0.00)->after('outstanding_checks');
            }
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'unrecorded_bank_credits')) {
                $table->decimal('unrecorded_bank_credits', 18, 2)->default(0.00)->after('unrecorded_bank_charges');
            }
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable()->after('reconciled_difference');
            }
            if (!Schema::hasColumn('rdr_bank_reconciliations', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->after('reconciled_by_user_id')->constrained('users')->nullOnDelete();
            }
        });

        // Modify status to string(30) to allow both RDR enum states and extended workflow states
        try {
            DB::statement("ALTER TABLE rdr_bank_reconciliations MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'IN_REVIEW'");
        } catch (\Throwable $e) {
            // Already compatible or altered
        }

        // 4. bank_reconciliation_items
        if (!Schema::hasTable('bank_reconciliation_items')) {
            Schema::create('bank_reconciliation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reconciliation_id')->constrained('rdr_bank_reconciliations')->cascadeOnDelete();
                $table->foreignId('bank_movement_id')->nullable()->constrained('bank_movements')->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->string('item_type', 30); // MATCHED, DEPOSIT_IN_TRANSIT, OUTSTANDING_CHECK, BANK_CHARGE, BANK_CREDIT, ERROR
                $table->decimal('amount', 18, 2);
                $table->string('reference', 100)->nullable();
                $table->string('concept', 255)->nullable();
                $table->decimal('difference', 18, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['reconciliation_id', 'item_type']);
            });
        }

        // 5. cash_flow_mappings
        if (!Schema::hasTable('cash_flow_mappings')) {
            Schema::create('cash_flow_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('category', 20); // OPERATING, INVESTING, FINANCING
                $table->string('flow_type', 10); // INFLOW, OUTFLOW
                $table->string('concept_name', 150);
                $table->string('account_prefix', 20)->nullable(); // e.g. 70, 60, 62, 63, 33, 107, 14
                $table->string('event_key', 50)->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['category', 'flow_type', 'is_active']);
            });
        }

        // 6. internal_transfers (between accounts and cash)
        if (!Schema::hasTable('internal_transfers')) {
            Schema::create('internal_transfers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('transfer_code', 60)->unique();
                $table->string('transfer_type', 30); // BANK_TO_BANK, CASH_TO_BANK, BANK_TO_CASH, TRANSFER_TO_CUT
                $table->string('source_type', 30); // BANK_ACCOUNT, CASH
                $table->foreignId('source_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
                $table->foreignId('source_arching_cash_id')->nullable()->constrained('arching_cashes')->nullOnDelete();
                $table->string('destination_type', 30); // BANK_ACCOUNT, CASH, CUT
                $table->foreignId('destination_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
                $table->foreignId('destination_fund_source_id')->nullable()->constrained('fund_sources')->nullOnDelete();
                $table->decimal('amount', 18, 2);
                $table->date('transfer_date');
                $table->string('reference_number', 60)->nullable();
                $table->string('concept', 255);
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
                $table->string('status', 30)->default('COMPLETED'); // DRAFT, COMPLETED, ANULLED
                $table->timestamps();
                $table->softDeletes();

                $table->index(['transfer_type', 'transfer_date', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_transfers');
        Schema::dropIfExists('cash_flow_mappings');
        Schema::dropIfExists('bank_reconciliation_items');

        Schema::table('rdr_bank_reconciliations', function (Blueprint $table) {
            if (Schema::hasColumn('rdr_bank_reconciliations', 'approved_by_user_id')) {
                $table->dropForeign(['approved_by_user_id']);
                $table->dropColumn('approved_by_user_id');
            }
            if (Schema::hasColumn('rdr_bank_reconciliations', 'bank_account_id')) {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
            if (Schema::hasColumn('rdr_bank_reconciliations', 'accounting_period_id')) {
                $table->dropForeign(['accounting_period_id']);
                $table->dropColumn('accounting_period_id');
            }
            $table->dropColumn([
                'book_calculated_balance',
                'uncredited_deposits',
                'outstanding_checks',
                'unrecorded_bank_charges',
                'unrecorded_bank_credits',
                'reconciled_at',
            ]);
        });

        Schema::dropIfExists('bank_movements');
        Schema::dropIfExists('bank_accounts');
    }
};
