<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(1);
            $table->string('codigo_producto', 50)->nullable();
            $table->string('codigo', 50)->nullable();
            $table->text('descripcion');
            $table->string('marca', 100)->default('SIN MARCA');
            $table->string('modelo', 100)->default('SIN MODELO');
            $table->string('serie', 100)->default('SIN SERIE');
            $table->decimal('costo', 12, 2)->default(0.00);
            $table->string('condicion', 10)->default('B');
            $table->string('tipo_adquisicion', 10)->default('C');
            $table->date('fecha_adquisicion')->nullable();
            $table->string('anio_adquisicion', 20)->nullable();
            $table->string('ubicacion', 255);
            $table->string('custodio', 255)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado_operativo', 20)->default('OPERATIVO');
            $table->string('foto_path', 255)->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['area_id', 'orden']);
            $table->index('codigo_producto');
            $table->index('codigo');
            $table->index('condicion');
            $table->index('is_reconciled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
