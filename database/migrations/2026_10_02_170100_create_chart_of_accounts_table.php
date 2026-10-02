<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Código contable PCGE (ej. 101, 10411, 1212, 40111, 70111)');
            $table->string('name', 255)->comment('Denominación de la cuenta');
            $table->unsignedTinyInteger('element')->comment('Elemento del PCGE (1 al 9, 0)');
            $table->unsignedTinyInteger('level')->default(1)->comment('1=Elemento, 2=Cuenta, 3=Subcuenta, 4=Divisionaria, 5=Subdivisionaria');
            $table->enum('nature', ['DEBIT', 'CREDIT'])->default('DEBIT')->comment('Naturaleza deudora o acreedora');
            $table->enum('classification', [
                'ACTIVO',
                'PASIVO',
                'PATRIMONIO',
                'GASTOS_NATURALEZA',
                'INGRESOS',
                'COSTOS_PRODUCCION',
                'GASTOS_FUNCION',
                'ORDEN',
            ])->default('ACTIVO');
            $table->boolean('accepts_movements')->default(false)->comment('TRUE si es cuenta imputable');
            $table->boolean('allows_movement')->default(false)->comment('Alias de accepts_movements');
            $table->boolean('requires_third_party')->default(false)->comment('Requiere RUC/DNI');
            $table->boolean('requires_cost_center')->default(false)->comment('Requiere Centro de Costo (Actividad Productiva)');
            $table->boolean('requires_fund_source')->default(false)->comment('Requiere Fuente de Financiamiento');
            $table->string('currency', 3)->default('PEN');
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->restrictOnDelete();
            $table->boolean('active')->default(true);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['code', 'accepts_movements']);
            $table->index(['element', 'classification']);
            $table->index(['active', 'accepts_movements']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
