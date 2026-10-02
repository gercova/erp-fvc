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
        Schema::table('series', function (Blueprint $table) {
            if (! Schema::hasColumn('series', 'idalmacen')) {
                $table->unsignedBigInteger('idalmacen')->nullable()->after('idcaja');
                $table->foreign('idalmacen')->references('id')->on('warehouses')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            if (Schema::hasColumn('series', 'idalmacen')) {
                $table->dropForeign(['idalmacen']);
                $table->dropColumn('idalmacen');
            }
        });
    }
};
