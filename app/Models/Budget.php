<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Budget extends Model
{
    use HasFactory, SoftDeletes, HasApprovals;

    protected $table = 'budgets';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'fiscal_year',
        'status', // DRAFT, EN_REVISION, APPROVED, ACTIVE, REJECTED, CLOSED
        'alert_threshold_percentage',
        'initial_total_amount',
        'current_total_amount',
        'notes',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'activated_at',
        'area_id',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'alert_threshold_percentage' => 'decimal:2',
        'initial_total_amount' => 'decimal:2',
        'current_total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Budget $budget) {
            if (empty($budget->uuid)) {
                $budget->uuid = (string) Str::uuid();
            }
            if (empty($budget->code)) {
                $budget->code = 'PRE-' . ($budget->fiscal_year ?? date('Y')) . '-' . strtoupper(Str::random(5));
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class, 'budget_id');
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(BudgetModification::class, 'budget_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Compatibility with HasApprovals trait
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['APPROVED', 'APROBADO', 'ACTIVE'], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isClosed(): bool
    {
        return $this->status === 'CLOSED';
    }

    public function recalculateTotals(): void
    {
        $initial = (float) $this->lines()->sum('allocated_amount');
        $current = (float) $this->lines()->sum('current_amount');
        $this->update([
            'initial_total_amount' => $initial,
            'current_total_amount' => $current,
        ]);
    }
}
