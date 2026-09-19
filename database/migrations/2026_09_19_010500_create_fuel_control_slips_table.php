<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_control_slips', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique(); // e.g. 0000040
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->date('fecha');
            $table->time('hora');
            $table->string('requerimiento_nro')->nullable();
            $table->string('orden_compra_nro')->nullable();
            $table->string('nombre_grifo');
            $table->string('vehiculo_maquina');
            $table->string('placa')->nullable();
            $table->string('kilometraje_horometro')->nullable();
            $table->text('actividad_comision');
            $table->string('facturar_a')->default('BOCASHI');
            $table->text('observaciones')->nullable();
            $table->string('atendido_por_grifo')->nullable();
            $table->string('recibido_por')->nullable();
            $table->decimal('total_general', 12, 2)->default(0);
            $table->string('status', 30)->default('PENDIENTE');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fuel_control_slip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fuel_control_slip_id')->constrained('fuel_control_slips')->cascadeOnDelete();
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad_medida', 20)->default('Gal.'); // Gal., Und., etc.
            $table->string('descripcion'); // Gasohol Regular, Diesel B5 S50, Aceite 2T, Aceite 20W50, Liquido de Freno
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_control_slip_items');
        Schema::dropIfExists('fuel_control_slips');
    }
};
