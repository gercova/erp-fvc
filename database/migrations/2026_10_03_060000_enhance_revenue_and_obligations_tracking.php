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
        // 1. Enhance agreement_installments
        Schema::table('agreement_installments', function (Blueprint $table) {
            if (!Schema::hasColumn('agreement_installments', 'milestone_condition')) {
                $table->string('milestone_condition', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('agreement_installments', 'technological_service_id')) {
                $table->foreignId('technological_service_id')->nullable()->after('agreement_id')
                    ->constrained('technological_services')->nullOnDelete();
            }
            if (!Schema::hasColumn('agreement_installments', 'alerted_thresholds')) {
                $table->json('alerted_thresholds')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('agreement_installments', 'adjustment_notes')) {
                $table->text('adjustment_notes')->nullable()->after('alerted_thresholds');
            }
            if (!Schema::hasColumn('agreement_installments', 'adjusted_by_user_id')) {
                $table->foreignId('adjusted_by_user_id')->nullable()->after('adjustment_notes')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // 2. Enhance agreement_obligations
        Schema::table('agreement_obligations', function (Blueprint $table) {
            if (!Schema::hasColumn('agreement_obligations', 'responsible_user_id')) {
                $table->foreignId('responsible_user_id')->nullable()->after('responsible_party')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('agreement_obligations', 'evidence_file_path')) {
                $table->string('evidence_file_path', 500)->nullable()->after('evidence_notes');
            }
            if (!Schema::hasColumn('agreement_obligations', 'alerted_thresholds')) {
                $table->json('alerted_thresholds')->nullable()->after('evidence_file_path');
            }
        });

        // 3. Enhance agreements
        Schema::table('agreements', function (Blueprint $table) {
            if (!Schema::hasColumn('agreements', 'alerted_thresholds')) {
                $table->json('alerted_thresholds')->nullable();
            }
        });

        // 4. Create agreement_alert_logs
        if (!Schema::hasTable('agreement_alert_logs')) {
            Schema::create('agreement_alert_logs', function (Blueprint $table) {
                $table->id();
                $table->string('alertable_type', 100);
                $table->unsignedBigInteger('alertable_id');
                $table->unsignedInteger('threshold_days'); // 30, 15, 7
                $table->date('target_date');
                $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->uuid('notification_id')->nullable();
                $table->string('alert_type', 50)->default('DEADLINE_WARNING');
                $table->text('message')->nullable();
                $table->dateTime('sent_at');
                $table->timestamps();

                $table->unique(['alertable_type', 'alertable_id', 'threshold_days'], 'idx_agr_alerts_unique');
                $table->index(['target_date', 'threshold_days']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreement_alert_logs');

        Schema::table('agreements', function (Blueprint $table) {
            if (Schema::hasColumn('agreements', 'alerted_thresholds')) {
                $table->dropColumn('alerted_thresholds');
            }
        });

        Schema::table('agreement_obligations', function (Blueprint $table) {
            if (Schema::hasColumn('agreement_obligations', 'responsible_user_id')) {
                $table->dropForeign(['responsible_user_id']);
                $table->dropColumn('responsible_user_id');
            }
            if (Schema::hasColumn('agreement_obligations', 'evidence_file_path')) {
                $table->dropColumn('evidence_file_path');
            }
            if (Schema::hasColumn('agreement_obligations', 'alerted_thresholds')) {
                $table->dropColumn('alerted_thresholds');
            }
        });

        Schema::table('agreement_installments', function (Blueprint $table) {
            if (Schema::hasColumn('agreement_installments', 'adjusted_by_user_id')) {
                $table->dropForeign(['adjusted_by_user_id']);
                $table->dropColumn('adjusted_by_user_id');
            }
            if (Schema::hasColumn('agreement_installments', 'technological_service_id')) {
                $table->dropForeign(['technological_service_id']);
                $table->dropColumn('technological_service_id');
            }
            if (Schema::hasColumn('agreement_installments', 'milestone_condition')) {
                $table->dropColumn('milestone_condition');
            }
            if (Schema::hasColumn('agreement_installments', 'alerted_thresholds')) {
                $table->dropColumn('alerted_thresholds');
            }
            if (Schema::hasColumn('agreement_installments', 'adjustment_notes')) {
                $table->dropColumn('adjustment_notes');
            }
        });
    }
};
