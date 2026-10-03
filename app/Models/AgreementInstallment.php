<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgreementInstallment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agreement_installments';

    protected $fillable = [
        'uuid',
        'agreement_id',
        'technological_service_id',
        'installment_number',
        'description',
        'milestone_condition',
        'due_date',
        'amount',
        'currency',
        'igv_affected',
        'status',
        'billing_id',
        'sale_note_id',
        'invoiced_at',
        'paid_at',
        'payment_reference',
        'notes',
        'alerted_thresholds',
        'adjustment_notes',
        'adjusted_by_user_id',
    ];

    protected $casts = [
        'status'              => InstallmentStatus::class,
        'due_date'            => 'date',
        'amount'              => 'decimal:2',
        'igv_affected'        => 'boolean',
        'installment_number'  => 'integer',
        'invoiced_at'         => 'datetime',
        'paid_at'             => 'datetime',
        'alerted_thresholds'  => 'array',
    ];

    protected static function booted(): void {
        static::creating(function (AgreementInstallment $installment) {
            if (empty($installment->uuid)) {
                $installment->uuid = (string) Str::uuid();
            }
            if (empty($installment->installment_number) && $installment->agreement_id) {
                $max = static::where('agreement_id', $installment->agreement_id)->max('installment_number');
                $installment->installment_number = ((int) $max) + 1;
            }
            if (empty($installment->due_date)) {
                $installment->due_date = $installment->scheduled_date ?? now()->toDateString();
            }
            if (empty($installment->description)) {
                $installment->description = "Cuota N° {$installment->installment_number}";
            }
            if (empty($installment->status)) {
                $installment->status = InstallmentStatus::PENDING;
            }
        });
    }

    public function agreement(): BelongsTo {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function technologicalService(): BelongsTo {
        return $this->belongsTo(TechnologicalService::class, 'technological_service_id');
    }

    public function billing(): BelongsTo {
        return $this->belongsTo(Billing::class, 'billing_id');
    }

    public function saleNote(): BelongsTo {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }

    public function adjustedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'adjusted_by_user_id');
    }

    /**
     * Checks if installment is eligible for invoicing.
     * Acceptance Criteria: an installment cannot be invoiced twice.
     */
    public function canBeInvoiced(): bool {
        return $this->billing_id === null &&
               $this->sale_note_id === null &&
               !$this->isInvoiced() &&
               !$this->isPaid();
    }

    public function isInvoiced(): bool {
        $statusVal = $this->status instanceof InstallmentStatus ? $this->status->value : (string)$this->status;
        return $this->billing_id !== null ||
               $this->sale_note_id !== null ||
               $statusVal === InstallmentStatus::INVOICED->value ||
               $this->invoiced_at !== null;
    }

    public function isPaid(): bool {
        return $this->isCollected();
    }

    public function isCollected(): bool {
        $statusVal = $this->status instanceof InstallmentStatus ? $this->status->value : (string)$this->status;
        return $statusVal === InstallmentStatus::COLLECTED->value ||
               $statusVal === InstallmentStatus::PAID->value ||
               $this->paid_at !== null;
    }

    public function isOverdue(): bool {
        $statusVal = $this->status instanceof InstallmentStatus ? $this->status->value : (string)$this->status;
        if ($statusVal === InstallmentStatus::OVERDUE->value) {
            return true;
        }
        if (!$this->isInvoiced() && !$this->isCollected() && $this->due_date) {
            return Carbon::parse($this->due_date)->startOfDay()->isPast();
        }
        return false;
    }

    /**
     * Synchronize installment status directly derived from vouchers and payments.
     */
    public function syncStatusFromVoucher(): void {
        if ($this->billing_id) {
            $billing = $this->billing;
            if ($billing) {
                $isPaid = ($billing->condicion_pago === 'contado' ||
                           $billing->estado_pago === 'PAGADO' ||
                           (property_exists($billing, 'estado') && $billing->estado === 1));

                $newStatus = $isPaid ? InstallmentStatus::COLLECTED : InstallmentStatus::INVOICED;
                $this->updateQuietly([
                    'status'      => $newStatus,
                    'invoiced_at' => $this->invoiced_at ?? $billing->created_at ?? now(),
                    'paid_at'     => $isPaid ? ($this->paid_at ?? now()) : null,
                ]);
                return;
            }
        }

        if ($this->sale_note_id) {
            $saleNote = $this->saleNote;
            if ($saleNote) {
                $isPaid = ($saleNote->condicion_pago === 'contado' ||
                           $saleNote->estado_pago === 'PAGADO' ||
                           $saleNote->estado === 1);

                $newStatus = $isPaid ? InstallmentStatus::COLLECTED : InstallmentStatus::INVOICED;
                $this->updateQuietly([
                    'status'      => $newStatus,
                    'invoiced_at' => $this->invoiced_at ?? $saleNote->created_at ?? now(),
                    'paid_at'     => $isPaid ? ($this->paid_at ?? now()) : null,
                ]);
                return;
            }
        }

        if ($this->due_date && Carbon::parse($this->due_date)->startOfDay()->isPast()) {
            $this->updateQuietly(['status' => InstallmentStatus::OVERDUE]);
        } else {
            $this->updateQuietly(['status' => InstallmentStatus::PENDING]);
        }
    }
}
