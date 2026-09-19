<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_area_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Cargo/puesto dentro de esa área (texto libre para mostrar en documentos:
            // "Docente", "Coordinador Académico", "Jefe de Área", "Administrador", "Director General")
            $table->string('cargo');

            // Nombrado, Contratado, CAS, etc. — aparece en la papeleta de vacaciones
            $table->string('condicion_laboral')->nullable();

            // Área principal del usuario, la que se usa para calcular a quién
            // escala un documento cuando el usuario tiene más de una asignación
            $table->boolean('is_primary')->default(true);

            $table->timestamps();

            $table->unique(['area_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_area_details');
    }
};