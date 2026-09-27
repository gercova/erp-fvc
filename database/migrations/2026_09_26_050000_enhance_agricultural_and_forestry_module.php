<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Mejoras a plantaciones: tipo de cultivo, frecuencia y fecha estimada
        if (Schema::hasTable('agricultural_plantations')) {
            Schema::table('agricultural_plantations', function (Blueprint $table) {
                if (!Schema::hasColumn('agricultural_plantations', 'crop_type')) {
                    $table->enum('crop_type', ['PERMANENT', 'SHORT_CYCLE'])->default('PERMANENT')->after('crop_species');
                }
                if (!Schema::hasColumn('agricultural_plantations', 'estimated_harvest_date')) {
                    $table->date('estimated_harvest_date')->nullable()->after('planting_date');
                }
                if (!Schema::hasColumn('agricultural_plantations', 'harvest_frequency_days')) {
                    $table->unsignedSmallInteger('harvest_frequency_days')->nullable()->default(15)->after('estimated_harvest_date');
                }
            });
        }

        // 2. Mejoras a cosechas de Bloque C (production_harvests): enlace a parcela y vivero para evitar duplicidad
        if (Schema::hasTable('production_harvests')) {
            Schema::table('production_harvests', function (Blueprint $table) {
                if (!Schema::hasColumn('production_harvests', 'agricultural_plot_id')) {
                    $table->foreignId('agricultural_plot_id')->nullable()->after('production_batch_id')->constrained('agricultural_plots')->nullOnDelete();
                }
                if (!Schema::hasColumn('production_harvests', 'agricultural_nursery_id')) {
                    $table->foreignId('agricultural_nursery_id')->nullable()->after('agricultural_plot_id')->constrained('agricultural_nurseries')->nullOnDelete();
                }
                if (!Schema::hasColumn('production_harvests', 'field_ticket_code')) {
                    $table->string('field_ticket_code', 80)->nullable()->after('harvest_date');
                }
                if (!Schema::hasColumn('production_harvests', 'field_weight_kg')) {
                    $table->decimal('field_weight_kg', 12, 3)->nullable()->after('quantity');
                }
            });
        }

        // 3. Mejoras a eventos pecuarios: fases de alimentación (starter, grower, finisher, etc.)
        if (Schema::hasTable('livestock_events')) {
            Schema::table('livestock_events', function (Blueprint $table) {
                if (!Schema::hasColumn('livestock_events', 'feeding_phase')) {
                    $table->enum('feeding_phase', ['STARTER', 'GROWER', 'FINISHER', 'MAINTENANCE', 'LACTATION', 'OTHER'])->nullable()->after('dosage_or_ration');
                }
            });
        }

        // 4. Mejoras a conciliación de rendimientos (campo vs facturación oficial)
        if (Schema::hasTable('agricultural_yield_logs')) {
            Schema::table('agricultural_yield_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('agricultural_yield_logs', 'field_ticket_code')) {
                    $table->string('field_ticket_code', 80)->nullable()->after('harvest_date');
                }
                if (!Schema::hasColumn('agricultural_yield_logs', 'field_reported_tonnage')) {
                    $table->decimal('field_reported_tonnage', 12, 3)->default(0)->after('harvested_quantity');
                }
                if (!Schema::hasColumn('agricultural_yield_logs', 'invoiced_tonnage')) {
                    $table->decimal('invoiced_tonnage', 12, 3)->default(0)->after('field_reported_tonnage');
                }
                if (!Schema::hasColumn('agricultural_yield_logs', 'weight_difference_tonnage')) {
                    $table->decimal('weight_difference_tonnage', 12, 3)->default(0)->after('invoiced_tonnage');
                }
                if (!Schema::hasColumn('agricultural_yield_logs', 'discrepancy_percent')) {
                    $table->decimal('discrepancy_percent', 6, 2)->default(0)->after('weight_difference_tonnage');
                }
                if (!Schema::hasColumn('agricultural_yield_logs', 'billing_id')) {
                    $table->foreignId('billing_id')->nullable()->after('production_harvest_id')->constrained('billings')->nullOnDelete();
                }
                if (!Schema::hasColumn('agricultural_yield_logs', 'sale_note_id')) {
                    $table->foreignId('sale_note_id')->nullable()->after('billing_id')->constrained('sale_notes')->nullOnDelete();
                }
                if (!Schema::hasColumn('agricultural_yield_logs', 'reconciliation_status')) {
                    $table->enum('reconciliation_status', ['MATCHED', 'DISCREPANCY', 'PENDING_INVOICE'])->default('PENDING_INVOICE')->after('discrepancy_percent');
                }
                if (!Schema::hasColumn('agricultural_yield_logs', 'reconciliation_notes')) {
                    $table->text('reconciliation_notes')->nullable()->after('notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agricultural_yield_logs')) {
            Schema::table('agricultural_yield_logs', function (Blueprint $table) {
                $table->dropForeign(['billing_id']);
                $table->dropForeign(['sale_note_id']);
                $table->dropColumn([
                    'field_ticket_code',
                    'field_reported_tonnage',
                    'invoiced_tonnage',
                    'weight_difference_tonnage',
                    'discrepancy_percent',
                    'billing_id',
                    'sale_note_id',
                    'reconciliation_status',
                    'reconciliation_notes'
                ]);
            });
        }

        if (Schema::hasTable('livestock_events')) {
            Schema::table('livestock_events', function (Blueprint $table) {
                $table->dropColumn('feeding_phase');
            });
        }

        if (Schema::hasTable('production_harvests')) {
            Schema::table('production_harvests', function (Blueprint $table) {
                $table->dropForeign(['agricultural_plot_id']);
                $table->dropForeign(['agricultural_nursery_id']);
                $table->dropColumn([
                    'agricultural_plot_id',
                    'agricultural_nursery_id',
                    'field_ticket_code',
                    'field_weight_kg'
                ]);
            });
        }

        if (Schema::hasTable('agricultural_plantations')) {
            Schema::table('agricultural_plantations', function (Blueprint $table) {
                $table->dropColumn(['crop_type', 'estimated_harvest_date', 'harvest_frequency_days']);
            });
        }
    }
};
