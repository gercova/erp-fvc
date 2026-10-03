<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_period_closures')) {
            Schema::table('accounting_period_closures', function (Blueprint $table) {
                $table->string('status', 30)->default('PENDIENTE')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_period_closures')) {
            Schema::table('accounting_period_closures', function (Blueprint $table) {
                $table->string('status', 30)->default('DRAFT')->change();
            });
        }
    }
};
