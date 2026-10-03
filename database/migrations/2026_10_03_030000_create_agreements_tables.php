<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. agreements
        if (!Schema::hasTable('agreements')) {
            Schema::create('agreements', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 50)->unique();
                $table->string('type', 30)->default('SPECIFIC'); // FRAMEWORK, SPECIFIC
                $table->foreignId('parent_agreement_id')->nullable()->constrained('agreements')->nullOnDelete();
                $table->string('title', 255);
                $table->text('objective');
                $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
                $table->string('counterparty_signatory_name', 150);
                $table->string('counterparty_signatory_role', 100);
                $table->string('counterparty_signatory_document', 20);
                $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
                $table->foreignId('coordinator_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('productive_activity_id')->nullable()->constrained('productive_activities')->nullOnDelete();
                $table->date('signature_date');
                $table->date('start_date');
                $table->date('end_date');
                $table->date('original_end_date');
                $table->string('currency', 3)->default('PEN');
                $table->decimal('total_amount', 14, 2)->default(0.00);
                $table->decimal('original_amount', 14, 2)->default(0.00);
                $table->decimal('counterparty_contribution', 14, 2)->default(0.00);
                $table->decimal('institution_contribution', 14, 2)->default(0.00);
                $table->string('status', 30)->default('DRAFT'); // DRAFT, IN_APPROVAL, ACTIVE, EXPIRING_SOON, EXPIRED, SETTLED, TERMINATED
                $table->boolean('requires_financial_settlement')->default(true);
                $table->string('resolution_number', 100)->nullable();
                $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'end_date']);
                $table->index('client_id');
                $table->index('coordinator_user_id');
            });
        }

        // 2. agreement_addenda
        if (!Schema::hasTable('agreement_addenda')) {
            Schema::create('agreement_addenda', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->unsignedInteger('addendum_number');
                $table->string('code', 60)->unique();
                $table->string('type', 40)->default('TIME_EXTENSION'); // TIME_EXTENSION, AMOUNT_MODIFICATION, SCOPE_CHANGE, MIXED
                $table->string('resolution_number', 100)->nullable();
                $table->text('justification');
                $table->date('previous_end_date');
                $table->date('new_end_date');
                $table->decimal('amount_delta', 14, 2)->default(0.00);
                $table->decimal('previous_total_amount', 14, 2);
                $table->decimal('new_total_amount', 14, 2);
                $table->date('signature_date');
                $table->unsignedBigInteger('document_approval_id')->nullable();
                $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['agreement_id', 'addendum_number']);
            });
        }

        // 3. agreement_obligations
        if (!Schema::hasTable('agreement_obligations')) {
            Schema::create('agreement_obligations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->string('responsible_party', 30)->default('INSTITUTION'); // INSTITUTION, COUNTERPARTY, JOINT
                $table->string('clause_reference', 50)->nullable();
                $table->string('title', 255);
                $table->text('description');
                $table->date('due_date');
                $table->string('status', 30)->default('PENDING'); // PENDING, IN_PROGRESS, COMPLETED, OVERDUE
                $table->dateTime('completed_at')->nullable();
                $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('evidence_notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['agreement_id', 'status']);
                $table->index(['status', 'due_date']);
            });
        }

        // 4. agreement_installments
        if (!Schema::hasTable('agreement_installments')) {
            Schema::create('agreement_installments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->unsignedInteger('installment_number');
                $table->string('description', 255);
                $table->date('due_date');
                $table->decimal('amount', 14, 2);
                $table->string('currency', 3)->default('PEN');
                $table->boolean('igv_affected')->default(false);
                $table->string('status', 30)->default('SCHEDULED'); // SCHEDULED, INVOICED, PAID, OVERDUE, CANCELLED
                $table->foreignId('billing_id')->nullable()->constrained('billings')->nullOnDelete();
                $table->foreignId('sale_note_id')->nullable()->constrained('sale_notes')->nullOnDelete();
                $table->dateTime('invoiced_at')->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->string('payment_reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['agreement_id', 'installment_number']);
                $table->index(['status', 'due_date']);
                $table->index('billing_id');
                $table->index('sale_note_id');
            });
        }

        // 5. agreement_documents
        if (!Schema::hasTable('agreement_documents')) {
            Schema::create('agreement_documents', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('agreement_id')->constrained('agreements')->cascadeOnDelete();
                $table->string('document_type', 40)->default('SIGNED_AGREEMENT'); // SIGNED_AGREEMENT, RESOLUTION, TECHNICAL_REPORT, SETTLEMENT_ACT, ADDENDUM_FILE, OTHER
                $table->string('title', 200);
                $table->string('file_path', 500);
                $table->string('file_name', 255);
                $table->unsignedInteger('file_size');
                $table->string('mime_type', 100);
                $table->unsignedInteger('version')->default(1);
                $table->foreignId('uploaded_by_user_id')->constrained('users')->restrictOnDelete();
                $table->timestamps();

                $table->index(['agreement_id', 'document_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_documents');
        Schema::dropIfExists('agreement_installments');
        Schema::dropIfExists('agreement_obligations');
        Schema::dropIfExists('agreement_addenda');
        Schema::dropIfExists('agreements');
    }
};
