<?php

namespace App\Models;

use App\Enums\ServiceEngagementStatus;
use App\Services\Agreements\AgreementCodeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServiceEngagement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_engagements';

    protected $fillable = [
        'uuid',
        'code',
        'agreement_id',
        'client_id',
        'technological_service_id',
        'productive_activity_id',
        'responsible_user_id',
        'description',
        'quantity',
        'unit_price',
        'total_amount',
        'currency',
        'start_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'billing_id',
        'sale_note_id',
        'settlement_notes',
    ];

    protected $casts = [
        'quantity'               => 'decimal:2',
        'unit_price'             => 'decimal:2',
        'total_amount'           => 'decimal:2',
        'start_date'             => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date'   => 'date',
        'status'                 => ServiceEngagementStatus::class,
    ];

    protected static function booted(): void {
        static::creating(function (ServiceEngagement $engagement) {
            if (empty($engagement->uuid)) {
                $engagement->uuid = (string) Str::uuid();
            }
            if (empty($engagement->code)) {
                $year = $engagement->start_date ? Carbon::parse($engagement->start_date)->year : (int) date('Y');
                $engagement->code = app(AgreementCodeService::class)->generateEngagementCode($year);
            }
            if (empty($engagement->total_amount)) {
                $engagement->total_amount = round(($engagement->quantity ?? 1) * ($engagement->unit_price ?? 0), 2);
            }
        });
    }

    public function agreement(): BelongsTo {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function technologicalService(): BelongsTo {
        return $this->belongsTo(TechnologicalService::class, 'technological_service_id');
    }

    public function productiveActivity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function costCenter(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function responsibleUser(): BelongsTo {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function billing(): BelongsTo {
        return $this->belongsTo(Billing::class, 'billing_id');
    }

    public function saleNote(): BelongsTo {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }

    public function sessions(): HasMany {
        return $this->hasMany(ServiceSession::class, 'service_engagement_id')->orderBy('session_number');
    }

    public function attendees(): HasMany {
        return $this->hasMany(ServiceAttendee::class, 'service_engagement_id');
    }

    public function deliverables(): HasMany {
        return $this->hasMany(ServiceDeliverable::class, 'service_engagement_id');
    }

    public function isUnderAgreement(): bool {
        return !is_null($this->agreement_id);
    }
}
