<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove any legacy stock rows for services (opcion = 2)
        $serviceIds = DB::table('products')->where('opcion', 2)->pluck('id');
        if ($serviceIds->isNotEmpty()) {
            DB::table('stock_products')->whereIn('idproducto', $serviceIds)->delete();
        }

        // 2. Add unique constraint so each physical product only exists once per warehouse
        Schema::table('stock_products', function (Blueprint $table) {
            $table->unique(['idalmacen', 'idproducto'], 'stock_products_idalmacen_idproducto_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock_products', function (Blueprint $table) {
            $table->dropUnique('stock_products_idalmacen_idproducto_unique');
        });
    }
};
