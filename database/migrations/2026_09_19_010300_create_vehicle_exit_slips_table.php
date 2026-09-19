<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_exit_slips', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('vehiculo');
            $table->string('solicitante_nombre');
            $table->string('chofer_nombre');
            $table->string('brevete_numero', 30)->nullable();
            $table->string('lugar');
            $table->text('motivo');
            $table->date('fecha_salida');
            $table->time('hora_salida');
            $table->date('fecha_retorno')->nullable();
            $table->time('hora_retorno')->nullable();
            $table->string('lugar_emision')->default('Uchiza');
            $table->text('observaciones')->nullable();
            $table->string('status', 30)->default('PENDIENTE');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_exit_slips');
    }
};
