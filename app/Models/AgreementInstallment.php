<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
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
        'installment_number',
        'description',
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
    ];

    protected $casts = [
        'status'             => InstallmentStatus::class,
        'due_date'           => 'date',
        'amount'             => 'decimal:2',
        'igv_affected'       => 'boolean',
        'installment_number' => 'integer',
        'invoiced_at'        => 'datetime',
        'paid_at'            => 'datetime',
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
        });
    }

    public function agreement(): BelongsTo {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function billing(): BelongsTo {
        return $this->belongsTo(Billing::class, 'billing_id');
    }

    public function saleNote(): BelongsTo {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }

    public function isInvoiced(): bool {
        return $this->billing_id !== null || $this->sale_note_id !== null || $this->status === InstallmentStatus::INVOICED;
    }

    public function isPaid(): bool {
        return $this->status === InstallmentStatus::PAID;
    }
}
