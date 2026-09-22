<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo', 50)->unique();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->string('titulo'); // ej: INVENTARIO GENERAL DEL ÁREA ADMINISTRACIÓN - TESORERÍA
            $table->string('periodo', 20)->default('2026');
            $table->date('fecha_inventario');
            $table->string('responsable'); // Responsable del área (ej: MOSQUERA RUIZ LEONIDAS)
            $table->string('realizado_por'); // Realizado por (ej: Tec. OSORIO SANCHEZ, CECILIA ISABEL)
            $table->string('status', 30)->default('BORRADOR'); // BORRADOR, EN_REVISION, APROBADO, OBSERVADO, ANULADO
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['area_id', 'periodo']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_inventories');
    }
};
