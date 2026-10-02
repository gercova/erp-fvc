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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->string('entry_number', 30);
            $table->date('entry_date');
            $table->string('entry_type', 20)->default('OPERATING');
            $table->string('concept', 500);
            $table->string('currency', 3)->default('PEN');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('total_debit', 18, 2)->default(0.00);
            $table->decimal('total_credit', 18, 2)->default(0.00);
            $table->string('status', 20)->default('POSTED');

            // Polymorphic source
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_key', 100)->nullable();
            $table->string('idempotency_key', 100)->nullable();

            // Reversal links
            $table->foreignId('reversed_by_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reverses_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            // Audit
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('posted_by_user_id')->nullable()->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            // Idempotency composite unique index
            $table->unique(['source_type', 'source_id', 'event_key'], 'uk_journal_source_event');
            $table->index(['accounting_period_id', 'entry_date']);
            $table->index('status');
        });

        // Add DB-level CHECK constraint enforcing sum(debit) = sum(credit)
        try {
            DB::statement('ALTER TABLE journal_entries ADD CONSTRAINT chk_journal_entry_balanced CHECK (total_debit = total_credit)');
        } catch (\Throwable $e) {
            // Some test drivers may not support alter table check constraints
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
