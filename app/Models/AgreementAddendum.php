<?php

namespace App\Models;

use App\Enums\AddendumType;
use App\Services\Agreements\AgreementCodeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AgreementAddendum extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'agreement_addenda';

    protected $fillable = [
        'uuid',
        'agreement_id',
        'addendum_number',
        'code',
        'type',
        'resolution_number',
        'justification',
        'previous_end_date',
        'new_end_date',
        'amount_delta',
        'previous_total_amount',
        'new_total_amount',
        'signature_date',
        'document_approval_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'type'                  => AddendumType::class,
        'previous_end_date'     => 'date',
        'new_end_date'          => 'date',
        'signature_date'        => 'date',
        'amount_delta'          => 'decimal:2',
        'previous_total_amount' => 'decimal:2',
        'new_total_amount'      => 'decimal:2',
        'addendum_number'       => 'integer',
    ];

    protected static function booted(): void {
        static::creating(function (AgreementAddendum $addendum) {
            if (empty($addendum->uuid)) {
                $addendum->uuid = (string) Str::uuid();
            }
            if (empty($addendum->addendum_number) && $addendum->agreement_id) {
                $maxNum = static::where('agreement_id', $addendum->agreement_id)->max('addendum_number');
                $addendum->addendum_number = ((int) $maxNum) + 1;
            }
            $agreement = $addendum->agreement ?? Agreement::find($addendum->agreement_id);
            if (empty($addendum->code) && $agreement) {
                $addendum->code = app(AgreementCodeService::class)->generateAddendumCode($agreement);
            }
            if ($agreement) {
                if (empty($addendum->previous_end_date)) {
                    $addendum->previous_end_date = $agreement->end_date;
                }
                if (empty($addendum->new_end_date)) {
                    $addendum->new_end_date = $addendum->previous_end_date;
                }
                if (empty($addendum->previous_total_amount)) {
                    $addendum->previous_total_amount = $agreement->total_amount ?? 0.00;
                }
                if (empty($addendum->new_total_amount)) {
                    $addendum->new_total_amount = round(($addendum->previous_total_amount ?? 0) + ($addendum->amount_delta ?? 0), 2);
                }
            }
            if (empty($addendum->signature_date)) {
                $addendum->signature_date = now()->toDateString();
            }
            if (empty($addendum->created_by_user_id)) {
                $addendum->created_by_user_id = auth()->id() ?? $agreement?->coordinator_user_id ?? 1;
            }
        });
    }

    public function agreement(): BelongsTo {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
