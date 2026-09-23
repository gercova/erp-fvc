<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_loans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('loan_code', 50)->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();

            // Clasificación del prestatario: STUDENT, FACULTY, ADMINISTRATIVE
            $table->string('borrower_type', 30)->default('STUDENT');
            $table->foreignId('borrower_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('borrower_name', 255);
            $table->string('borrower_document', 50);
            $table->string('borrower_code', 50)->nullable();
            $table->string('borrower_career_or_area', 255)->nullable();
            $table->string('borrower_phone', 50)->nullable();
            $table->string('borrower_email', 150)->nullable();

            // Fechas del préstamo
            $table->dateTime('loan_date');
            $table->dateTime('expected_return_date');
            $table->dateTime('actual_return_date')->nullable();

            // Estado del préstamo: PRESTADO, DEVUELTO, DEVUELTO_OBSERVADO, EXTRAVIADO
            $table->string('status', 30)->default('PRESTADO');

            // Condiciones físicas y destino
            $table->string('initial_condition', 20)->default('B');
            $table->string('return_condition', 20)->nullable();
            $table->string('destination', 255);
            $table->text('purpose')->nullable();
            $table->text('observations')->nullable();
            $table->text('return_observations')->nullable();

            // Personal responsable
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['area_id', 'status']);
            $table->index(['borrower_type', 'status']);
            $table->index('asset_id');
            $table->index('loan_date');
            $table->index('expected_return_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_loans');
    }
};
