<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_campaigns')) {
            Schema::create('production_campaigns', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities')->cascadeOnDelete();
                $table->string('campaign_code', 50);
                $table->string('name', 150);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->text('production_targets')->nullable();
                $table->decimal('target_quantity', 12, 2)->default(0);
                $table->string('target_unit', 30)->default('KG');
                $table->decimal('budget_allocated', 14, 2)->default(0);
                $table->enum('status', ['planned', 'in_progress', 'closed'])->default('planned');
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('production_batches')) {
            Schema::create('production_batches', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('production_campaign_id')->constrained('production_campaigns')->cascadeOnDelete();
                $table->string('batch_code', 50);
                $table->string('phase_name', 100);
                $table->enum('phase_type', ['start', 'growth', 'fattening', 'sowing', 'maintenance', 'harvest', 'other'])->default('other');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->decimal('initial_quantity', 12, 2)->default(0);
                $table->decimal('current_quantity', 12, 2)->default(0);
                $table->string('unit_measure', 30)->default('UNIDADES');
                $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('production_labor_costs')) {
            Schema::create('production_labor_costs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('production_batch_id')->constrained('production_batches')->cascadeOnDelete();
                $table->string('task_description', 200);
                $table->string('worker_name', 150);
                $table->foreignId('worker_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('task_date');
                $table->decimal('hours_worked', 8, 2)->default(0);
                $table->decimal('hourly_rate', 10, 2)->default(0);
                $table->decimal('labor_cost', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('production_raw_materials')) {
            Schema::create('production_raw_materials', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name', 150);
                $table->enum('category', ['fertilizer', 'seed', 'medication', 'feed', 'insecticide_fungicide', 'other'])->default('other');
                $table->string('unit_of_measurement', 30);
                $table->decimal('default_unit_cost', 12, 2)->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('production_input_movements')) {
            Schema::create('production_input_movements', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('production_raw_material_id')->constrained('production_raw_materials');
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->foreignId('production_campaign_id')->nullable()->constrained('production_campaigns')->nullOnDelete();
                $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
                $table->enum('movement_type', ['inflow_purchase', 'outflow_consumption', 'adjustment'])->default('outflow_consumption');
                $table->date('movement_date');
                $table->decimal('quantity', 12, 3);
                $table->decimal('unit_cost', 12, 2);
                $table->decimal('total_cost', 14, 2);
                $table->foreignId('buy_id')->nullable()->constrained('buys')->nullOnDelete();
                $table->foreignId('detail_buy_id')->nullable()->constrained('detail_buys')->nullOnDelete();
                $table->foreignId('registered_by_user_id')->constrained('users');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('produced_items')) {
            Schema::create('produced_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->string('name', 150);
                $table->string('unit_of_measurement', 30);
                $table->decimal('standard_cost', 12, 2)->default(0);
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->boolean('is_published_to_sales')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('production_harvests')) {
            Schema::create('production_harvests', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('produced_item_id')->constrained('produced_items');
                $table->foreignId('production_campaign_id')->constrained('production_campaigns');
                $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
                $table->date('harvest_date');
                $table->decimal('quantity', 12, 3);
                $table->string('quality_grade', 50)->default('ESTÁNDAR');
                $table->decimal('unit_cost_calculated', 12, 2)->default(0);
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->foreignId('stock_product_id')->nullable()->constrained('stock_products')->nullOnDelete();
                $table->foreignId('registered_by_user_id')->constrained('users');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('activity_orders')) {
            Schema::create('activity_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('order_code', 50);
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->foreignId('client_id')->constrained('clients');
                $table->foreignId('produced_item_id')->constrained('produced_items');
                $table->date('order_date');
                $table->date('expected_delivery_date')->nullable();
                $table->decimal('quantity', 12, 3);
                $table->decimal('unit_price', 12, 2);
                $table->decimal('total_amount', 14, 2);
                $table->decimal('advance_payment', 14, 2)->default(0);
                $table->decimal('balance_pending', 14, 2)->default(0);
                $table->enum('status', ['pending', 'confirmed', 'delivered', 'invoiced', 'cancelled'])->default('pending');
                $table->foreignId('sale_note_id')->nullable()->constrained('sale_notes')->nullOnDelete();
                $table->foreignId('billing_id')->nullable()->constrained('billings')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->constrained('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('activity_sales_attributions')) {
            Schema::create('activity_sales_attributions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('productive_activity_id')->constrained('productive_activities');
                $table->foreignId('produced_item_id')->constrained('produced_items');
                $table->foreignId('production_campaign_id')->nullable()->constrained('production_campaigns')->nullOnDelete();
                $table->foreignId('detail_sale_note_id')->nullable()->constrained('detail_sale_notes')->nullOnDelete();
                $table->foreignId('detail_billing_id')->nullable()->constrained('detail_billings')->nullOnDelete();
                $table->decimal('quantity_sold', 12, 3);
                $table->decimal('unit_sale_price', 12, 2);
                $table->decimal('revenue_amount', 14, 2);
                $table->date('sale_date');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_sales_attributions');
        Schema::dropIfExists('activity_orders');
        Schema::dropIfExists('production_harvests');
        Schema::dropIfExists('produced_items');
        Schema::dropIfExists('production_input_movements');
        Schema::dropIfExists('production_raw_materials');
        Schema::dropIfExists('production_labor_costs');
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('production_campaigns');
    }
};
