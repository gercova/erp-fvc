<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_tracking_logs')) {
            try {
                Schema::table('activity_tracking_logs', function (Blueprint $table) {
                    $table->dropIndex('act_track_act_date_idx');
                });
            } catch (\Throwable $e) {
                // Ignore if index doesn't exist
            }

            Schema::table('activity_tracking_logs', function (Blueprint $table) {
                if (Schema::hasColumn('activity_tracking_logs', 'event_date') && !Schema::hasColumn('activity_tracking_logs', 'log_date')) {
                    $table->renameColumn('event_date', 'log_date');
                } elseif (!Schema::hasColumn('activity_tracking_logs', 'log_date')) {
                    $table->date('log_date')->after('user_id');
                }

                if (Schema::hasColumn('activity_tracking_logs', 'progress_percentage') && !Schema::hasColumn('activity_tracking_logs', 'progress_percent')) {
                    $table->renameColumn('progress_percentage', 'progress_percent');
                } elseif (!Schema::hasColumn('activity_tracking_logs', 'progress_percent')) {
                    $table->decimal('progress_percent', 5, 2)->default(0)->after('log_date');
                }

                if (Schema::hasColumn('activity_tracking_logs', 'status_marked') && !Schema::hasColumn('activity_tracking_logs', 'status')) {
                    $table->renameColumn('status_marked', 'status');
                } elseif (!Schema::hasColumn('activity_tracking_logs', 'status')) {
                    $table->string('status', 50)->default('ACTIVA')->after('progress_percent');
                }
            });

            try {
                Schema::table('activity_tracking_logs', function (Blueprint $table) {
                    $table->index(['productive_activity_id', 'log_date'], 'act_track_act_log_date_idx');
                });
            } catch (\Throwable $e) {
                // Ignore if already indexed
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('activity_tracking_logs')) {
            try {
                Schema::table('activity_tracking_logs', function (Blueprint $table) {
                    $table->dropIndex('act_track_act_log_date_idx');
                });
            } catch (\Throwable $e) {
            }

            Schema::table('activity_tracking_logs', function (Blueprint $table) {
                if (Schema::hasColumn('activity_tracking_logs', 'log_date') && !Schema::hasColumn('activity_tracking_logs', 'event_date')) {
                    $table->renameColumn('log_date', 'event_date');
                }
                if (Schema::hasColumn('activity_tracking_logs', 'progress_percent') && !Schema::hasColumn('activity_tracking_logs', 'progress_percentage')) {
                    $table->renameColumn('progress_percent', 'progress_percentage');
                }
                if (Schema::hasColumn('activity_tracking_logs', 'status') && !Schema::hasColumn('activity_tracking_logs', 'status_marked')) {
                    $table->renameColumn('status', 'status_marked');
                }
            });
        }
    }
};
