<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('dirigido_a')->default('Director Gerente del IESTP "Francisco Vigo Caballero"');
            $table->string('de');
            $table->string('cargo');
            $table->date('fecha');
            $table->text('finalidad');
            $table->string('fuente_financiamiento')->default('programa_estudios'); // programa_estudios | actividad_productiva | plan
            $table->string('fuente_especificar')->nullable();
            $table->text('justificacion')->nullable();
            $table->text('condiciones_admin')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 30)->default('PENDIENTE'); // PENDIENTE, EN_REVISION, APROBADO, OBSERVADO, RECHAZADO, ANULADO
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_id')->constrained('requisitions')->cascadeOnDelete();
            $table->unsignedInteger('item_number')->default(1);
            $table->decimal('cantidad', 10, 2);
            $table->text('descripcion');
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('precio_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_items');
        Schema::dropIfExists('requisitions');
    }
};
