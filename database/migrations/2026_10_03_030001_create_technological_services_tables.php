<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 6. technological_services
        if (!Schema::hasTable('technological_services')) {
            Schema::create('technological_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 50)->unique(); // SERV-2026-0001
                $table->foreignId('product_id')->unique()->constrained('products')->restrictOnDelete();
                $table->string('name', 200);
                $table->text('description')->nullable();
                $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
                $table->foreignId('productive_activity_id')->constrained('productive_activities')->restrictOnDelete();
                $table->string('category', 40)->default('TECHNICAL_ASSISTANCE'); // TRAINING, TECHNICAL_ASSISTANCE, CONSULTING, LABORATORY_ANALYSIS, OTHER
                $table->string('delivery_modality', 30)->default('IN_PERSON'); // IN_PERSON, VIRTUAL, HYBRID, FIELD
                $table->decimal('unit_price', 12, 2)->default(0.00);
                $table->string('currency', 3)->default('PEN');
                $table->unsignedInteger('estimated_hours')->default(0);
                $table->boolean('requires_deliverable')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('area_id');
                $table->index('productive_activity_id');
            });
        }

        // 7. service_engagements
        if (!Schema::hasTable('service_engagements')) {
            Schema::create('service_engagements', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 60)->unique(); // ORD-2026-0001
                $table->foreignId('agreement_id')->nullable()->constrained('agreements')->nullOnDelete();
                $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
                $table->foreignId('technological_service_id')->constrained('technological_services')->restrictOnDelete();
                $table->foreignId('productive_activity_id')->constrained('productive_activities')->restrictOnDelete();
                $table->foreignId('responsible_user_id')->constrained('users')->restrictOnDelete();
                $table->text('description');
                $table->decimal('quantity', 10, 2)->default(1.00);
                $table->decimal('unit_price', 12, 2);
                $table->decimal('total_amount', 14, 2);
                $table->string('currency', 3)->default('PEN');
                $table->date('start_date');
                $table->date('expected_delivery_date');
                $table->date('actual_delivery_date')->nullable();
                $table->string('status', 30)->default('DRAFT'); // DRAFT, IN_PROGRESS, COMPLETED, CANCELLED
                $table->foreignId('billing_id')->nullable()->constrained('billings')->nullOnDelete();
                $table->foreignId('sale_note_id')->nullable()->constrained('sale_notes')->nullOnDelete();
                $table->text('settlement_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['agreement_id', 'status']);
                $table->index('client_id');
                $table->index('technological_service_id');
                $table->index('billing_id');
                $table->index('sale_note_id');
            });
        }

        // 8. service_sessions
        if (!Schema::hasTable('service_sessions')) {
            Schema::create('service_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('service_engagement_id')->constrained('service_engagements')->cascadeOnDelete();
                $table->unsignedInteger('session_number');
                $table->string('topic', 255);
                $table->foreignId('instructor_user_id')->constrained('users')->restrictOnDelete();
                $table->date('session_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('location', 150);
                $table->string('status', 30)->default('SCHEDULED'); // SCHEDULED, CONDUCTED, CANCELLED, RESCHEDULED
                $table->text('observations')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['service_engagement_id', 'session_date']);
            });
        }

        // 9. service_attendees
        if (!Schema::hasTable('service_attendees')) {
            Schema::create('service_attendees', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('service_session_id')->constrained('service_sessions')->cascadeOnDelete();
                $table->foreignId('service_engagement_id')->constrained('service_engagements')->cascadeOnDelete();
                $table->string('full_name', 200);
                $table->string('dni_or_document', 20);
                $table->string('email', 120)->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('organization', 150)->nullable();
                $table->boolean('attended')->default(false);
                $table->decimal('evaluation_score', 4, 2)->nullable();
                $table->string('certificate_code', 50)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['service_session_id', 'dni_or_document']);
                $table->index('service_engagement_id');
                $table->index('dni_or_document');
            });
        }

        // 10. service_deliverables
        if (!Schema::hasTable('service_deliverables')) {
            Schema::create('service_deliverables', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('service_engagement_id')->constrained('service_engagements')->cascadeOnDelete();
                $table->string('deliverable_name', 200);
                $table->text('description');
                $table->date('due_date');
                $table->date('submission_date')->nullable();
                $table->string('status', 30)->default('PENDING'); // PENDING, SUBMITTED, UNDER_REVIEW, APPROVED, REJECTED
                $table->string('file_path', 500)->nullable();
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approval_date')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['service_engagement_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_deliverables');
        Schema::dropIfExists('service_attendees');
        Schema::dropIfExists('service_sessions');
        Schema::dropIfExists('service_engagements');
        Schema::dropIfExists('technological_services');
    }
};
