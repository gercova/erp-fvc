<?php

namespace App\Models;

use App\Enums\ServiceDeliverableStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServiceDeliverable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_deliverables';

    protected $fillable = [
        'uuid',
        'service_engagement_id',
        'deliverable_name',
        'description',
        'due_date',
        'submission_date',
        'status',
        'file_path',
        'approved_by_user_id',
        'approval_date',
        'rejection_reason',
    ];

    protected $casts = [
        'due_date'        => 'date',
        'submission_date' => 'date',
        'approval_date'   => 'datetime',
        'status'          => ServiceDeliverableStatus::class,
    ];

    protected static function booted(): void {
        static::creating(function (ServiceDeliverable $deliverable) {
            if (empty($deliverable->uuid)) {
                $deliverable->uuid = (string) Str::uuid();
            }
        });
    }

    public function engagement(): BelongsTo {
        return $this->belongsTo(ServiceEngagement::class, 'service_engagement_id');
    }

    public function approver(): BelongsTo {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
