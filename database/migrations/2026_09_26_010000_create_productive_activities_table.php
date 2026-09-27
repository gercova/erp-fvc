<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('productive_activities')) {
            Schema::create('productive_activities', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 30)->unique();
                $table->string('name', 150);
                $table->string('cost_center_code', 30)->nullable()->index();
                $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
                $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['ACTIVA', 'INACTIVA', 'EN_MANTENIMIENTO'])->default('ACTIVA');
                $table->text('description')->nullable();
                $table->integer('order_index')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('productive_activities');
    }
};
