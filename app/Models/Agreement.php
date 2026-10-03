<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use App\Enums\AgreementType;
use App\Services\Agreements\AgreementCodeService;
use App\Traits\HasApprovals;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Agreement extends Model
{
    use HasFactory, SoftDeletes, HasApprovals;

    protected $table = 'agreements';

    protected $fillable = [
        'uuid',
        'code',
        'type',
        'parent_agreement_id',
        'title',
        'objective',
        'client_id',
        'counterparty_signatory_name',
        'counterparty_signatory_role',
        'counterparty_signatory_document',
        'area_id',
        'coordinator_user_id',
        'productive_activity_id',
        'signature_date',
        'start_date',
        'end_date',
        'original_end_date',
        'currency',
        'total_amount',
        'original_amount',
        'counterparty_contribution',
        'institution_contribution',
        'status',
        'requires_financial_settlement',
        'resolution_number',
        'created_by_user_id',
    ];

    protected $casts = [
        'type'                         => AgreementType::class,
        'status'                       => AgreementStatus::class,
        'signature_date'               => 'date',
        'start_date'                   => 'date',
        'end_date'                     => 'date',
        'original_end_date'            => 'date',
        'total_amount'                 => 'decimal:2',
        'original_amount'              => 'decimal:2',
        'counterparty_contribution'    => 'decimal:2',
        'institution_contribution'     => 'decimal:2',
        'requires_financial_settlement'=> 'boolean',
    ];

    protected static function booted(): void {
        static::creating(function (Agreement $agreement) {
            if (empty($agreement->uuid)) {
                $agreement->uuid = (string) Str::uuid();
            }
            if (empty($agreement->code)) {
                $year = $agreement->start_date ? Carbon::parse($agreement->start_date)->year : (int) date('Y');
                $agreement->code = app(AgreementCodeService::class)->generateAgreementCode($year);
            }
            if (empty($agreement->original_end_date) && !empty($agreement->end_date)) {
                $agreement->original_end_date = $agreement->end_date;
            }
            if (empty($agreement->original_amount) && !empty($agreement->total_amount)) {
                $agreement->original_amount = $agreement->total_amount;
            }
            if (empty($agreement->signature_date)) {
                $agreement->signature_date = $agreement->start_date ?? now()->toDateString();
            }
            if (empty($agreement->objective)) {
                $agreement->objective = $agreement->title ?? 'Finalidad institucional por definir';
            }
            if (empty($agreement->counterparty_signatory_name)) {
                $agreement->counterparty_signatory_name = $agreement->client?->nombres ?? 'Representante Legal';
            }
            if (empty($agreement->counterparty_signatory_role)) {
                $agreement->counterparty_signatory_role = 'Representante Legal';
            }
            if (empty($agreement->counterparty_signatory_document)) {
                $agreement->counterparty_signatory_document = $agreement->client?->nro_documento ?? '-';
            }
            if (empty($agreement->created_by_user_id)) {
                $agreement->created_by_user_id = auth()->id() ?? $agreement->coordinator_user_id;
            }
        });
    }

    // Relationships
    public function parentAgreement(): BelongsTo {
        return $this->belongsTo(Agreement::class, 'parent_agreement_id');
    }

    public function specificAgreements(): HasMany {
        return $this->hasMany(Agreement::class, 'parent_agreement_id');
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function area(): BelongsTo {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function coordinator(): BelongsTo {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function productiveActivity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function costCenter(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function addenda(): HasMany {
        return $this->hasMany(AgreementAddendum::class, 'agreement_id')->orderBy('addendum_number');
    }

    public function obligations(): HasMany {
        return $this->hasMany(AgreementObligation::class, 'agreement_id');
    }

    public function installments(): HasMany {
        return $this->hasMany(AgreementInstallment::class, 'agreement_id')->orderBy('installment_number');
    }

    public function documents(): HasMany {
        return $this->hasMany(AgreementDocument::class, 'agreement_id');
    }

    public function serviceEngagements(): HasMany {
        return $this->hasMany(ServiceEngagement::class, 'agreement_id');
    }

    // Helper Methods
    public function isFramework(): bool {
        return $this->type === AgreementType::FRAMEWORK;
    }

    public function isSpecific(): bool {
        return $this->type === AgreementType::SPECIFIC;
    }

    public function isActive(): bool {
        return $this->status === AgreementStatus::ACTIVE;
    }

    public function isExpired(): bool {
        return $this->status === AgreementStatus::EXPIRED;
    }

    public function isSettled(): bool {
        return $this->status === AgreementStatus::SETTLED;
    }

    public function daysRemaining(): int {
        if (!$this->end_date) {
            return 0;
        }
        return (int) now()->startOfDay()->diffInDays(Carbon::parse($this->end_date)->startOfDay(), false);
    }
}
