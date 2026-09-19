<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_exit_slips', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('apellidos_nombres');
            $table->string('dni', 20);
            $table->string('condicion_laboral')->default('Docente Nombrado'); // Docente Nombrado, Docente Contratado, Administrativo Nombrado, etc.
            $table->string('cargo_especialidad');
            $table->string('area_programa_estudios');
            $table->string('motivo')->default('Goce de descanso de vacaciones');
            $table->date('fecha_desde');
            $table->date('fecha_hasta');
            $table->unsignedInteger('total_dias')->default(1);
            $table->string('resolucion_directoral')->nullable(); // R.D. Nº que autoriza las vacaciones
            $table->text('declaracion')->nullable();
            $table->string('lugar_emision')->default('Uchiza');
            $table->string('status', 30)->default('PENDIENTE');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_exit_slips');
    }
};
