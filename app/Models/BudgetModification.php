<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BudgetModification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'budget_modifications';

    protected $fillable = [
        'uuid',
        'budget_id',
        'modification_code',
        'type', // ALLOCATION, CANCELLATION, TRANSFER
        'source_budget_line_id',
        'destination_budget_line_id',
        'amount',
        'justification',
        'status', // APPLIED, DRAFT, REJECTED
        'created_by_user_id',
        'approved_by_user_id',
        'applied_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (BudgetModification $mod) {
            if (empty($mod->uuid)) {
                $mod->uuid = (string) Str::uuid();
            }
            if (empty($mod->modification_code)) {
                $mod->modification_code = 'MOD-' . date('Y') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    public function sourceLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class, 'source_budget_line_id');
    }

    public function destinationLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class, 'destination_budget_line_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
