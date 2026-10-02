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
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->integer('line_number');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('debit', 18, 2)->default(0.00);
            $table->decimal('credit', 18, 2)->default(0.00);
            $table->decimal('debit_usd', 18, 4)->default(0.0000);
            $table->decimal('credit_usd', 18, 4)->default(0.0000);
            $table->string('glosa', 255)->nullable();

            // Analytical dimensions
            $table->string('third_party_type', 50)->nullable();
            $table->unsignedBigInteger('third_party_id')->nullable();
            $table->string('third_party_document', 20)->nullable();
            $table->foreignId('cost_center_id')->nullable()->constrained('productive_activities')->restrictOnDelete();
            $table->foreignId('fund_source_id')->nullable()->constrained('fund_sources')->restrictOnDelete();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('document_reference', 50)->nullable();

            $table->timestamps();

            $table->unique(['journal_entry_id', 'line_number']);
            $table->index(['account_id', 'journal_entry_id']);
            $table->index('cost_center_id');
            $table->index('fund_source_id');
            $table->index(['third_party_type', 'third_party_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
