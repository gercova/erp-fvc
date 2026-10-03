<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('agreement_audit_logs')) {
            Schema::create('agreement_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->string('action', 50)->default('STATUS_CHANGE'); // STATUS_CHANGE, SUBMIT_APPROVAL, APPROVE_STEP, OBSERVE, REJECT, ADDENDUM_APPLIED, TERMINATE
                $table->string('previous_status', 30)->nullable();
                $table->string('new_status', 30)->nullable();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['agreement_id', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_audit_logs');
    }
};
