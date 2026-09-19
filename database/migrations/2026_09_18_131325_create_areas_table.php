<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');

            // Jerarquía: cada área puede depender de otra (auto-referencia)
            $table->foreignId('parent_id')->nullable()->constrained('areas')->nullOnDelete();

            // Naturaleza del área, útil para agrupar en UI y para reglas de negocio
            // direccion_general | area | unidad | programa_academico | organo_consultivo
            $table->string('type')->default('area');

            // Responsable directo del área (Coordinador, Jefe, Administrador, Director General, etc.)
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Nivel dentro del organigrama, solo para referencia/orden visual (1 = Dirección General)
            $table->unsignedTinyInteger('level')->default(1);

            // Áreas de línea de coordinación/asesoría (líneas punteadas en el organigrama:
            // Secretaría de Dirección, Consejo Asesor, etc.). No forman parte obligatoria
            // de la cadena de aprobación de documentos, aunque sí de la estructura visual.
            $table->boolean('is_advisory')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};