<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('activity_collaborators')) {
            Schema::create('activity_collaborators', function (Blueprint $table) {
                $table->id();
                $table->foreignId('productive_activity_id')->constrained('productive_activities')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50)->default('COLLABORATOR'); // COLLABORATOR, TECHNICIAN, SUPERVISOR
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['productive_activity_id', 'user_id'], 'act_collab_act_user_unique');
                $table->index(['user_id', 'is_active'], 'act_collab_user_active_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_collaborators');
    }
};
