<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BudgetApproval extends Model
{
    use HasFactory, SoftDeletes, HasApprovals;

    protected $table = 'budget_approvals';

    protected $fillable = [
        'uuid',
        'budget_id',
        'status', // PENDIENTE, EN_REVISION, APROBADO, OBSERVADO, RECHAZADO
        'created_by_user_id',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (BudgetApproval $approval) {
            if (empty($approval->uuid)) {
                $approval->uuid = (string) Str::uuid();
            }
        });
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
