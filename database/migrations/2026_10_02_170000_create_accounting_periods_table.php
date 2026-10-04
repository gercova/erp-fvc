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
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('fiscal_year')->comment('Año fiscal (ej. 2026)');
            $table->integer('month')->comment('Mes (1..12, 0=Apertura, 13=Cierre)');
            $table->string('period_code', 10)->unique()->comment('Formato YYYY-MM (ej. 2026-10)');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['OPEN', 'SOFT_CLOSED', 'LOCKED'])->default('OPEN');
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['fiscal_year', 'month', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
