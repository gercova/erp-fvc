<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. budgets
        if (!Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 60)->unique();
                $table->string('name', 255);
                $table->year('fiscal_year');
                $table->string('status', 30)->default('DRAFT'); // DRAFT, EN_REVISION, APPROVED, ACTIVE, REJECTED, CLOSED
                $table->decimal('alert_threshold_percentage', 5, 2)->default(90.00);
                $table->decimal('initial_total_amount', 18, 2)->default(0.00);
                $table->decimal('current_total_amount', 18, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['fiscal_year', 'status']);
            });
        }

        // 2. budget_lines
        if (!Schema::hasTable('budget_lines')) {
            Schema::create('budget_lines', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
                $table->string('line_code', 60)->nullable();
                $table->foreignId('chart_of_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
                $table->string('account_code', 30)->nullable();
                $table->string('category_name', 150)->nullable();
                $table->foreignId('productive_activity_id')->nullable()->constrained('productive_activities')->nullOnDelete();
                $table->string('cost_center_code', 60)->nullable();
                $table->foreignId('fund_source_id')->nullable()->constrained('fund_sources')->nullOnDelete();
                $table->unsignedTinyInteger('period_month'); // 1..12
                $table->decimal('allocated_amount', 18, 2)->default(0.00);
                $table->decimal('modified_amount', 18, 2)->default(0.00);
                $table->decimal('current_amount', 18, 2)->default(0.00);
                $table->decimal('alert_threshold_percentage', 5, 2)->nullable();
                $table->timestamp('alert_sent_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['budget_id', 'period_month']);
                $table->index(['chart_of_account_id', 'period_month']);
                $table->index(['productive_activity_id', 'period_month']);
                $table->index(['fund_source_id', 'period_month']);
            });
        }

        // 3. budget_modifications
        if (!Schema::hasTable('budget_modifications')) {
            Schema::create('budget_modifications', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
                $table->string('modification_code', 60)->unique();
                $table->string('type', 30); // ALLOCATION, CANCELLATION, TRANSFER
                $table->foreignId('source_budget_line_id')->nullable()->constrained('budget_lines')->nullOnDelete();
                $table->foreignId('destination_budget_line_id')->nullable()->constrained('budget_lines')->nullOnDelete();
                $table->decimal('amount', 18, 2);
                $table->text('justification');
                $table->string('status', 30)->default('APPLIED');
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['budget_id', 'type']);
            });
        }

        // 4. budget_approvals (DocumentApproval Type 11 helper)
        if (!Schema::hasTable('budget_approvals')) {
            Schema::create('budget_approvals', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
                $table->string('status', 30)->default('PENDIENTE');
                $table->foreignId('created_by_user_id')->constrained('users');
                $table->timestamp('approved_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['budget_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_approvals');
        Schema::dropIfExists('budget_modifications');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
    }
};
