<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Parcelas y Lotes de Terreno (Superficie, Ubicación, Coordenadas)
        if (!Schema::hasTable('agricultural_plots')) {
            Schema::create('agricultural_plots', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities')->cascadeOnDelete();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->string('sector_location', 200);
                $table->decimal('area_hectares', 10, 4);
                $table->string('topography', 80)->nullable(); // PLANA, ONDULADA, COLINOSA
                $table->string('soil_type', 100)->nullable(); // FRANCO_ARCILLOSO, ALUVIAL, etc.
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->json('polygon_coordinates')->nullable(); // Polígono GeoJSON
                $table->enum('status', ['IN_PRODUCTION', 'FALLOW_REST', 'PREPARATION', 'ABANDONED'])->default('IN_PRODUCTION');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Cultivos y Plantaciones Instaladas por Parcela
        if (!Schema::hasTable('agricultural_plantations')) {
            Schema::create('agricultural_plantations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('agricultural_plot_id')->constrained('agricultural_plots')->cascadeOnDelete();
                $table->string('crop_species', 150); // Palma Aceitera, Cacao, Café, Teca, Bolaina
                $table->string('variety', 100)->nullable(); // p. ej. Tenera, CCN-51, Catimor
                $table->date('planting_date');
                $table->unsignedInteger('plant_count')->default(0);
                $table->string('spacing_meters', 50)->nullable(); // 9x9 tresbolillo, 3x3, etc.
                $table->decimal('expected_yield_per_ha', 10, 2)->default(0); // Ton/ha/año
                $table->decimal('age_years', 5, 2)->nullable();
                $table->enum('status', ['VEGETATIVE_DEVELOPMENT', 'FULL_PRODUCTION', 'DECLINING', 'RENOVATION', 'ERADICATED'])->default('FULL_PRODUCTION');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Viveros Forestales y Agrícolas (Especies, Cantidad, Etapa)
        if (!Schema::hasTable('agricultural_nurseries')) {
            Schema::create('agricultural_nurseries', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
                $table->string('name', 150);
                $table->string('location', 200);
                $table->string('species_name', 150);
                $table->enum('stage', ['GERMINATION', 'SEEDLING_CONTAINER', 'HARDENING_ACCLIMATIZATION', 'READY_FOR_FIELD', 'DISPATCHED'])->default('GERMINATION');
                $table->unsignedInteger('initial_quantity');
                $table->unsignedInteger('current_quantity');
                $table->decimal('survival_rate', 5, 2)->default(100.00);
                $table->date('sowing_date');
                $table->date('estimated_dispatch_date')->nullable();
                $table->foreignId('in_charge_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Unidades Pecuarias (Individuales o por Lotes - Bovinos, Porcinos, Cuyes, Peces)
        if (!Schema::hasTable('livestock_units')) {
            Schema::create('livestock_units', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->foreignId('productive_unit_id')->nullable()->constrained('productive_units')->nullOnDelete();
                $table->enum('species', ['CATTLE', 'PIG', 'GUINEA_PIG', 'POULTRY', 'FISH_POND', 'SHEEP_GOAT', 'OTHER']);
                $table->string('breed', 100)->nullable(); // Brown Swiss, Gyr, Landrace, etc.
                $table->enum('tracking_type', ['INDIVIDUAL', 'BATCH'])->default('INDIVIDUAL');
                $table->string('identifier_code', 80); // Arete, tatuaje, código de lote o poza
                $table->unsignedInteger('batch_head_count')->default(1);
                $table->enum('sex', ['MALE', 'FEMALE', 'MIXED_BATCH'])->default('FEMALE');
                $table->date('birth_or_entry_date');
                $table->decimal('entry_weight_kg', 8, 2)->nullable();
                $table->decimal('current_weight_kg', 8, 2)->nullable();
                $table->string('mother_identifier', 80)->nullable();
                $table->string('father_identifier', 80)->nullable();
                $table->enum('status', ['ACTIVE', 'GESTATION', 'LACTATION', 'FATTENING', 'QUARANTINE', 'SOLD', 'SLAUGHTERED', 'DECEASED', 'TRANSFERRED'])->default('ACTIVE');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['productive_activity_id', 'species', 'status'], 'ls_units_act_spec_idx');
            });
        }

        // 5. Eventos Pecuarios (Sanitarios, Alimentación, Pesaje, Bajas, Costos Asociados)
        if (!Schema::hasTable('livestock_events')) {
            Schema::create('livestock_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('livestock_unit_id')->constrained('livestock_units')->cascadeOnDelete();
                $table->enum('event_type', ['HEALTH_TREATMENT', 'VACCINATION', 'FEEDING_LOG', 'WEIGHT_CONTROL', 'BREEDING_SERVICE', 'BIRTH', 'WEANING', 'MORTALITY_DISPOSAL', 'SALE_TRANSFER']);
                $table->date('event_date');
                $table->text('description');
                $table->string('dosage_or_ration', 100)->nullable();
                $table->unsignedInteger('head_affected_count')->default(1);

                // Vinculación a insumos del catálogo abierto de producción
                $table->foreignId('production_raw_material_id')->nullable()->constrained('production_raw_materials')->nullOnDelete();
                $table->decimal('input_quantity_used', 10, 3)->default(0);
                $table->decimal('input_cost', 12, 2)->default(0);
                $table->decimal('labor_cost', 12, 2)->default(0);
                $table->decimal('total_cost', 12, 2)->default(0);

                $table->foreignId('recorded_by_user_id')->constrained('users');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['livestock_unit_id', 'event_type', 'event_date'], 'ls_events_type_date_idx');
            });
        }

        // 6. Registros Técnicos de Producción y Comparativos Interanuales
        if (!Schema::hasTable('agricultural_yield_logs')) {
            Schema::create('agricultural_yield_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('agricultural_plot_id')->constrained('agricultural_plots');
                $table->foreignId('agricultural_plantation_id')->nullable()->constrained('agricultural_plantations')->nullOnDelete();
                $table->foreignId('production_campaign_id')->nullable()->constrained('production_campaigns')->nullOnDelete();
                $table->foreignId('production_harvest_id')->nullable()->constrained('production_harvests')->nullOnDelete();
                $table->date('harvest_date');
                $table->year('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->decimal('harvested_quantity', 12, 3); // Toneladas, Racimos, Quintales
                $table->string('unit_of_measurement', 30)->default('TM');
                $table->decimal('area_harvested_ha', 10, 4);
                $table->decimal('yield_per_hectare', 10, 3); // TM/ha
                $table->decimal('cumulative_year_tonnage', 12, 3)->default(0);
                $table->decimal('previous_year_tonnage', 12, 3)->default(0); // Comparativo año anterior
                $table->string('quality_grade', 50)->default('PRIMERA');
                $table->foreignId('registered_by_user_id')->constrained('users');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['agricultural_plot_id', 'period_year', 'period_month'], 'agri_yield_period_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agricultural_yield_logs');
        Schema::dropIfExists('livestock_events');
        Schema::dropIfExists('livestock_units');
        Schema::dropIfExists('agricultural_nurseries');
        Schema::dropIfExists('agricultural_plantations');
        Schema::dropIfExists('agricultural_plots');
    }
};
