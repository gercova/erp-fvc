<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->unsignedBigInteger('document_id');
            $table->unsignedInteger('step_order')->default(1);
            $table->string('role_name', 50); // SOLICITANTE, COORD_PE, JEFE_INMEDIATO, ADMINISTRACION, DIRECTOR_GENERAL, ABASTECIMIENTO, etc.
            $table->string('label'); // Título de la casilla de firma
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_name')->nullable();
            $table->string('approver_cargo')->nullable();
            $table->string('status', 30)->default('PENDIENTE'); // PENDIENTE, APROBADO, OBSERVADO, RECHAZADO, OMITIDO
            $table->text('observations')->nullable();
            $table->string('signature_token', 64)->nullable();
            $table->text('signature_data')->nullable(); // Imagen o hash de firma
            $table->timestamp('signed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
            $table->index(['approver_id', 'status']);
            $table->index(['role_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};
