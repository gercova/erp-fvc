<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductionLaborCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_labor_costs';

    protected $fillable = [
        'uuid',
        'production_batch_id',
        'task_description',
        'worker_name',
        'worker_user_id',
        'task_date',
        'hours_worked',
        'hourly_rate',
        'labor_cost',
        'notes',
    ];

    protected $casts = [
        'task_date' => 'date',
        'hours_worked' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'labor_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductionLaborCost $labor) {
            if (empty($labor->uuid)) {
                $labor->uuid = (string) Str::uuid();
            }
            if (empty($labor->labor_cost) && $labor->hours_worked && $labor->hourly_rate) {
                $labor->labor_cost = $labor->hours_worked * $labor->hourly_rate;
            }
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function workerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_user_id');
    }
}
