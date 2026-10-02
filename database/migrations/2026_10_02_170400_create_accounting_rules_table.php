<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounting_rules', function (Blueprint $table) {
            $table->id();
            $table->string('event_code', 50)->unique();
            $table->string('description', 255);
            $table->string('source_type', 100)->nullable();
            $table->string('document_type_code', 10)->nullable();
            $table->foreignId('payment_method_id')->nullable()->constrained('pay_modes')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(10);
            $table->timestamps();

            $table->index(['event_code', 'is_active']);
        });

        Schema::create('rule_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_rule_id')->constrained('accounting_rules')->cascadeOnDelete();
            $table->enum('line_type', ['DEBIT', 'CREDIT']);
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->string('calculation_type', 50)->default('TOTAL')->comment('TOTAL, SUBTOTAL, IGV, FIXED');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('cost_center_source', 50)->default('DOCUMENT')->comment('DOCUMENT, FIXED, NONE');
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['accounting_rule_id', 'line_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void 
    {
        Schema::dropIfExists('rule_lines');
        Schema::dropIfExists('accounting_rules');
    }
};
