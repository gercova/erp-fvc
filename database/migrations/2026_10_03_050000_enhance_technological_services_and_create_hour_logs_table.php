<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance service_engagements
        Schema::table('service_engagements', function (Blueprint $table) {
            if (!Schema::hasColumn('service_engagements', 'delivery_modality')) {
                $table->string('delivery_modality', 30)->default('IN_PERSON')->after('description');
            }
            if (!Schema::hasColumn('service_engagements', 'contracted_hours')) {
                $table->decimal('contracted_hours', 10, 2)->default(0.00)->after('quantity');
            }
            if (!Schema::hasColumn('service_engagements', 'consumed_hours')) {
                $table->decimal('consumed_hours', 10, 2)->default(0.00)->after('contracted_hours');
            }
            if (!Schema::hasColumn('service_engagements', 'hourly_rate')) {
                $table->decimal('hourly_rate', 12, 2)->nullable()->after('unit_price');
            }
            if (!Schema::hasColumn('service_engagements', 'allow_pool_overage')) {
                $table->boolean('allow_pool_overage')->default(false)->after('status');
            }
            if (!Schema::hasColumn('service_engagements', 'min_attendance_percent')) {
                $table->decimal('min_attendance_percent', 5, 2)->default(80.00)->after('allow_pool_overage');
            }
            if (!Schema::hasColumn('service_engagements', 'low_balance_threshold_hours')) {
                $table->decimal('low_balance_threshold_hours', 10, 2)->default(5.00)->after('min_attendance_percent');
            }
            if (!Schema::hasColumn('service_engagements', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('settlement_notes');
            }
            if (!Schema::hasColumn('service_engagements', 'closed_by_user_id')) {
                $table->foreignId('closed_by_user_id')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('service_engagements', 'closure_summary')) {
                $table->text('closure_summary')->nullable()->after('settlement_notes');
            }
        });

        // 2. Enhance service_sessions
        Schema::table('service_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('service_sessions', 'duration_hours')) {
                $table->decimal('duration_hours', 6, 2)->default(0.00)->after('end_time');
            }
        });

        // 3. Enhance service_attendees
        Schema::table('service_attendees', function (Blueprint $table) {
            if (!Schema::hasColumn('service_attendees', 'certificate_issued_at')) {
                $table->dateTime('certificate_issued_at')->nullable()->after('certificate_code');
            }
            if (!Schema::hasColumn('service_attendees', 'certificate_hash')) {
                $table->string('certificate_hash', 64)->nullable()->after('certificate_issued_at');
            }
        });

        // 4. Enhance service_deliverables
        Schema::table('service_deliverables', function (Blueprint $table) {
            if (!Schema::hasColumn('service_deliverables', 'client_signoff_date')) {
                $table->dateTime('client_signoff_date')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('service_deliverables', 'client_signoff_name')) {
                $table->string('client_signoff_name', 150)->nullable()->after('client_signoff_date');
            }
            if (!Schema::hasColumn('service_deliverables', 'client_signoff_notes')) {
                $table->text('client_signoff_notes')->nullable()->after('client_signoff_name');
            }
            if (!Schema::hasColumn('service_deliverables', 'client_signoff_user_id')) {
                $table->foreignId('client_signoff_user_id')->nullable()->after('client_signoff_notes')->constrained('users')->nullOnDelete();
            }
        });

        // 5. Create service_hour_logs (for Technical Assistance and Consulting hour deduction)
        if (!Schema::hasTable('service_hour_logs')) {
            Schema::create('service_hour_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('service_engagement_id')->constrained('service_engagements')->cascadeOnDelete();
                $table->foreignId('specialist_user_id')->constrained('users')->restrictOnDelete();
                $table->date('log_date');
                $table->decimal('hours', 6, 2);
                $table->string('modality', 30)->default('FIELD'); // FIELD, IN_PERSON, VIRTUAL, LAB
                $table->string('activity_performed', 255);
                $table->string('location_or_farm', 200)->nullable();
                $table->string('client_contact_name', 150)->nullable();
                $table->boolean('client_signed')->default(false);
                $table->text('observations')->nullable();
                $table->boolean('is_authorized_overage')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['service_engagement_id', 'log_date']);
                $table->index('specialist_user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_hour_logs');

        Schema::table('service_deliverables', function (Blueprint $table) {
            $table->dropForeign(['client_signoff_user_id']);
            $table->dropColumn(['client_signoff_date', 'client_signoff_name', 'client_signoff_notes', 'client_signoff_user_id']);
        });

        Schema::table('service_attendees', function (Blueprint $table) {
            $table->dropColumn(['certificate_issued_at', 'certificate_hash']);
        });

        Schema::table('service_sessions', function (Blueprint $table) {
            $table->dropColumn(['duration_hours']);
        });

        Schema::table('service_engagements', function (Blueprint $table) {
            $table->dropForeign(['closed_by_user_id']);
            $table->dropColumn([
                'delivery_modality',
                'contracted_hours',
                'consumed_hours',
                'hourly_rate',
                'allow_pool_overage',
                'min_attendance_percent',
                'low_balance_threshold_hours',
                'closure_summary',
                'closed_at',
                'closed_by_user_id',
            ]);
        });
    }
};
