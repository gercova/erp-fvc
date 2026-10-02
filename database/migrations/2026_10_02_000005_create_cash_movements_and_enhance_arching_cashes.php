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
        if (!Schema::hasTable('cash_movements')) {
            Schema::create('cash_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('idarqueocaja')->constrained('arching_cashes')->cascadeOnDelete();
                $table->foreignId('idusuario')->constrained('users');
                $table->string('tipo', 20); // 'ingreso', 'egreso'
                $table->decimal('monto', 12, 2);
                $table->string('motivo', 255)->nullable();
                $table->date('fecha');
                $table->time('hora');
                $table->smallInteger('estado')->default(1);
                $table->timestamps();
            });
        }

        Schema::table('arching_cashes', function (Blueprint $table) {
            if (!Schema::hasColumn('arching_cashes', 'monto_estimado')) {
                $table->decimal('monto_estimado', 12, 2)->nullable()->after('monto_final');
            }
            if (!Schema::hasColumn('arching_cashes', 'diferencia')) {
                $table->decimal('diferencia', 12, 2)->nullable()->after('monto_estimado');
            }
            if (!Schema::hasColumn('arching_cashes', 'total_ingresos')) {
                $table->decimal('total_ingresos', 12, 2)->default(0)->after('diferencia');
            }
            if (!Schema::hasColumn('arching_cashes', 'total_egresos')) {
                $table->decimal('total_egresos', 12, 2)->default(0)->after('total_ingresos');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arching_cashes', function (Blueprint $table) {
            if (Schema::hasColumn('arching_cashes', 'total_egresos')) {
                $table->dropColumn('total_egresos');
            }
            if (Schema::hasColumn('arching_cashes', 'total_ingresos')) {
                $table->dropColumn('total_ingresos');
            }
            if (Schema::hasColumn('arching_cashes', 'diferencia')) {
                $table->dropColumn('diferencia');
            }
            if (Schema::hasColumn('arching_cashes', 'monto_estimado')) {
                $table->dropColumn('monto_estimado');
            }
        });

        Schema::dropIfExists('cash_movements');
    }
};
