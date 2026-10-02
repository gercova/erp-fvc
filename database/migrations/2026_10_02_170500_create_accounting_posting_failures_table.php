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
        Schema::create('accounting_posting_failures', function (Blueprint $table) {
            $table->id();
            $table->string('event_name', 150);
            $table->string('source_type', 150)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_key', 100)->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message');
            $table->longText('stack_trace')->nullable();
            $table->unsignedInteger('attempts')->default(1);
            $table->string('status', 30)->default('FAILED'); // FAILED, REPROCESSED, IGNORED
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('reprocessed_at')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'idx_fail_source');
            $table->index(['status', 'event_name'], 'idx_fail_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_posting_failures');
    }
};
