<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AssetLoan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'loan_code',
        'asset_id',
        'area_id',
        'borrower_type',
        'borrower_user_id',
        'borrower_name',
        'borrower_document',
        'borrower_code',
        'borrower_career_or_area',
        'borrower_phone',
        'borrower_email',
        'loan_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'initial_condition',
        'return_condition',
        'destination',
        'purpose',
        'observations',
        'return_observations',
        'user_id',
        'received_by_user_id',
    ];

    protected $casts = [
        'loan_date'            => 'datetime',
        'expected_return_date' => 'datetime',
        'actual_return_date'   => 'datetime',
    ];

    protected $attributes = [
        'status'            => 'PRESTADO',
        'initial_condition' => 'B',
        'borrower_type'     => 'STUDENT',
    ];

    protected static function booted(): void {
        static::creating(function (AssetLoan $loan) {
            if (empty($loan->uuid)) {
                $loan->uuid = (string) Str::uuid();
            }

            if (empty($loan->loan_code)) {
                $year = $loan->loan_date ? Carbon::parse($loan->loan_date)->format('Y') : date('Y');
                $count = static::whereYear('loan_date', $year)->count() + 1;
                $loan->loan_code = sprintf('PRE-%s-%04d', $year, $count);
            }
        });
    }

    public function asset(): BelongsTo {
        return $this->belongsTo(Asset::class);
    }

    public function area(): BelongsTo {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function borrowerUser(): BelongsTo {
        return $this->belongsTo(User::class, 'borrower_user_id');
    }

    public function receivedByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    // Scopes
    public function scopeActive($query) {
        return $query->where('status', 'PRESTADO');
    }

    public function scopeReturned($query) {
        return $query->whereIn('status', ['DEVUELTO', 'DEVUELTO_OBSERVADO']);
    }

    public function scopeOverdue($query) {
        return $query->where('status', 'PRESTADO')->where('expected_return_date', '<', now());
    }

    public function scopeByBorrowerType($query, $type) {
        if (!empty($type)) {
            return $query->where('borrower_type', $type);
        }
        return $query;
    }

    // Accessors
    public function getIsOverdueAttribute(): bool {
        return $this->status === 'PRESTADO' && $this->expected_return_date && $this->expected_return_date->isPast();
    }

    public function getBorrowerTypeLabelAttribute(): string {
        return match ($this->borrower_type) {
            'STUDENT'        => 'Estudiante',
            'FACULTY'        => 'Docente',
            'ADMINISTRATIVE' => 'Personal Administrativo',
            default          => $this->borrower_type,
        };
    }

    public function getBorrowerTypeBadgeAttribute(): string {
        return match ($this->borrower_type) {
            'STUDENT'        => '<span class="badge bg-info text-white px-2 py-1">Estudiante</span>',
            'FACULTY'        => '<span class="badge bg-primary text-white px-2 py-1">Docente</span>',
            'ADMINISTRATIVE' => '<span class="badge bg-secondary text-white px-2 py-1">Administrativo</span>',
            default          => '<span class="badge bg-light text-dark px-2 py-1">' . e($this->borrower_type) . '</span>',
        };
    }

    public function getStatusLabelAttribute(): string {
        if ($this->is_overdue) {
            return 'Vencido';
        }

        return match ($this->status) {
            'PRESTADO'           => 'En Préstamo',
            'DEVUELTO'           => 'Devuelto',
            'DEVUELTO_OBSERVADO' => 'Devuelto con Obs.',
            'EXTRAVIADO'         => 'Extraviado / Dañado',
            default              => $this->status,
        };
    }

    public function getStatusBadgeAttribute(): string {
        if ($this->is_overdue) {
            return '<span class="badge bg-danger text-white px-2 py-1">Vencido</span>';
        }

        return match ($this->status) {
            'PRESTADO'           => '<span class="badge bg-primary text-white px-2 py-1">En Préstamo</span>',
            'DEVUELTO'           => '<span class="badge bg-success text-white px-2 py-1">Devuelto</span>',
            'DEVUELTO_OBSERVADO' => '<span class="badge bg-warning text-dark px-2 py-1">Devuelto con Obs.</span>',
            'EXTRAVIADO'         => '<span class="badge bg-dark text-white px-2 py-1">Extraviado</span>',
            default              => '<span class="badge bg-light text-dark px-2 py-1">' . e($this->status) . '</span>',
        };
    }

    public function getInitialConditionLabelAttribute(): string {
        return match ($this->initial_condition) {
            'B'     => 'Bueno (B)',
            'R'     => 'Regular (R)',
            'M'     => 'Malo (M)',
            'BAJA'  => 'Baja',
            default => $this->initial_condition ?? 'Bueno (B)',
        };
    }

    public function getReturnConditionLabelAttribute(): ?string {
        if (!$this->return_condition) {
            return null;
        }

        return match ($this->return_condition) {
            'B'     => 'Bueno (B)',
            'R'     => 'Regular (R)',
            'M'     => 'Malo (M)',
            'BAJA'  => 'Baja',
            default => $this->return_condition,
        };
    }
}
