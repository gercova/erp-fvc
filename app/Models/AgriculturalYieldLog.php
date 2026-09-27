<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgriculturalYieldLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agricultural_yield_logs';

    protected $fillable = [
        'uuid',
        'agricultural_plot_id',
        'agricultural_plantation_id',
        'production_campaign_id',
        'production_harvest_id',
        'billing_id',
        'sale_note_id',
        'harvest_date',
        'period_year',
        'period_month',
        'field_ticket_code',
        'harvested_quantity',
        'field_reported_tonnage',
        'invoiced_tonnage',
        'weight_difference_tonnage',
        'discrepancy_percent',
        'unit_of_measurement',
        'area_harvested_ha',
        'yield_per_hectare',
        'cumulative_year_tonnage',
        'previous_year_tonnage',
        'quality_grade',
        'reconciliation_status',
        'registered_by_user_id',
        'notes',
        'reconciliation_notes',
    ];

    protected $casts = [
        'harvest_date' => 'date',
        'period_year' => 'integer',
        'period_month' => 'integer',
        'harvested_quantity' => 'decimal:3',
        'field_reported_tonnage' => 'decimal:3',
        'invoiced_tonnage' => 'decimal:3',
        'weight_difference_tonnage' => 'decimal:3',
        'discrepancy_percent' => 'decimal:2',
        'area_harvested_ha' => 'decimal:4',
        'yield_per_hectare' => 'decimal:3',
        'cumulative_year_tonnage' => 'decimal:3',
        'previous_year_tonnage' => 'decimal:3',
    ];

    protected static function booted(): void
    {
        static::creating(function (AgriculturalYieldLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
            if ($log->area_harvested_ha > 0 && empty($log->yield_per_hectare)) {
                $log->yield_per_hectare = round($log->harvested_quantity / $log->area_harvested_ha, 3);
            }
            if (isset($log->field_reported_tonnage, $log->invoiced_tonnage) && $log->invoiced_tonnage > 0) {
                $diff = $log->field_reported_tonnage - $log->invoiced_tonnage;
                $log->weight_difference_tonnage = round($diff, 3);
                $log->discrepancy_percent = round(($diff / $log->field_reported_tonnage) * 100, 2);
                $log->reconciliation_status = abs($diff) < 0.05 ? 'MATCHED' : 'DISCREPANCY';
            }
        });
    }

    public function plot(): BelongsTo
    {
        return $this->belongsTo(AgriculturalPlot::class, 'agricultural_plot_id');
    }

    public function plantation(): BelongsTo
    {
        return $this->belongsTo(AgriculturalPlantation::class, 'agricultural_plantation_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(ProductionCampaign::class, 'production_campaign_id');
    }

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(ProductionHarvest::class, 'production_harvest_id');
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class, 'billing_id');
    }

    public function saleNote(): BelongsTo
    {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function getReconciliationStatusBadgeAttribute(): string
    {
        return match ($this->reconciliation_status) {
            'MATCHED'         => '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>Conciliado</span>',
            'DISCREPANCY'     => '<span class="badge bg-danger text-white"><i class="fas fa-exclamation-triangle me-1"></i>Discrepancia</span>',
            'PENDING_INVOICE' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pendiente Facturación</span>',
            default           => '<span class="badge bg-secondary">' . e($this->reconciliation_status) . '</span>',
        };
    }
}
