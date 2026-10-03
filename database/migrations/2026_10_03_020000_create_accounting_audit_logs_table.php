<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounting_audit_logs')) {
            Schema::create('accounting_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50)->index(); // POST_ENTRY, REVERSE_ENTRY, CLOSE_PERIOD, REOPEN_PERIOD, ANNUAL_CLOSE, etc.
                $table->string('auditable_type', 100)->nullable();
                $table->unsignedBigInteger('auditable_id')->nullable();
                $table->string('entry_number', 50)->nullable()->index();
                $table->string('period_code', 20)->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                $table->index(['auditable_type', 'auditable_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_audit_logs');
    }
};
