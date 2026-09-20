<?php

namespace App\Traits;

use App\Models\Area;
use App\Models\DocumentApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasApprovals
{
    public function approvals(): MorphMany {
        return $this->morphMany(DocumentApproval::class, 'document')->orderBy('step_order');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function area(): BelongsTo {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function currentPendingApproval(): ?DocumentApproval {
        return $this->approvals()->where('status', 'PENDIENTE')->orderBy('step_order')->first();
    }

    public function isFullyApproved(): bool {
        return $this->status === 'APROBADO' || (
            $this->approvals()->exists() &&
            $this->approvals()->where('status', '!=', 'APROBADO')->count() === 0
        );
    }

    public function getStatusBadgeAttribute(): string {
        return match ($this->status) {
            'APROBADO'      => '<span class="badge bg-success text-white">Aprobado</span>',
            'EN_REVISION'   => '<span class="badge bg-info text-white">En Revisión</span>',
            'PENDIENTE'     => '<span class="badge bg-warning text-dark">Pendiente</span>',
            'OBSERVADO'     => '<span class="badge bg-secondary text-white">Observado</span>',
            'RECHAZADO'     => '<span class="badge bg-danger text-white">Rechazado</span>',
            'ANULADO'       => '<span class="badge bg-dark text-white">Anulado</span>',
            default         => '<span class="badge bg-light text-dark">' . e($this->status) . '</span>',
        };
    }
}
