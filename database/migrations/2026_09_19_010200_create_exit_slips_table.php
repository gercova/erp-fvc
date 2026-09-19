<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exit_slips', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('nombres_apellidos');
            $table->string('tipo_personal', 30)->default('DOCENTE'); // DOCENTE | ADMINISTRATIVO
            $table->date('fecha_salida');
            $table->time('hora_salida');
            $table->date('fecha_retorno')->nullable();
            $table->time('hora_retorno')->nullable();
            $table->string('destino')->nullable();
            $table->string('motivo', 50); // Comisión de Servicios, Salud, Asunto Judicial, Asunto Personal, Otros
            $table->string('motivo_especificar')->nullable();
            $table->string('lugar')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('lugar_emision')->default('Uchiza');
            $table->text('nota_salud')->nullable();
            $table->string('status', 30)->default('PENDIENTE');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exit_slips');
    }
};
