<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productive_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('productive_activities', 'type')) {
                $table->enum('type', [
                    'AGRICULTURAL',
                    'FORESTRY',
                    'AQUACULTURE',
                    'LIVESTOCK',
                    'INSTITUTIONAL',
                    'SERVICES'
                ])->default('AGRICULTURAL')->after('name');
            }

            if (!Schema::hasColumn('productive_activities', 'default_fund_source_id')) {
                $table->foreignId('default_fund_source_id')->nullable()->after('head_user_id')->constrained('fund_sources')->nullOnDelete();
            }

            if (!Schema::hasColumn('productive_activities', 'execution_progress_percent')) {
                $table->decimal('execution_progress_percent', 5, 2)->default(0)->after('status');
            }

            if (!Schema::hasColumn('productive_activities', 'monitoring_observations')) {
                $table->text('monitoring_observations')->nullable()->after('execution_progress_percent');
            }
        });

        if (!Schema::hasTable('activity_tracking_logs')) {
            Schema::create('activity_tracking_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users');
                $table->date('log_date');
                $table->decimal('progress_percent', 5, 2)->default(0);
                $table->string('status', 50)->default('ACTIVA');
                $table->text('comment');
                $table->timestamps();

                $table->index(['productive_activity_id', 'log_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_tracking_logs');

        Schema::table('productive_activities', function (Blueprint $table) {
            if (Schema::hasColumn('productive_activities', 'default_fund_source_id')) {
                $table->dropForeign(['default_fund_source_id']);
                $table->dropColumn('default_fund_source_id');
            }
            if (Schema::hasColumn('productive_activities', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('productive_activities', 'execution_progress_percent')) {
                $table->dropColumn('execution_progress_percent');
            }
            if (Schema::hasColumn('productive_activities', 'monitoring_observations')) {
                $table->dropColumn('monitoring_observations');
            }
        });
    }
};
