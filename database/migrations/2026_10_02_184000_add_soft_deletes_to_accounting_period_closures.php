<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('accounting_period_closures') && !Schema::hasColumn('accounting_period_closures', 'deleted_at')) {
            Schema::table('accounting_period_closures', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_period_closures') && Schema::hasColumn('accounting_period_closures', 'deleted_at')) {
            Schema::table('accounting_period_closures', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
