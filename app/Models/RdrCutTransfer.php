<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RdrCutTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rdr_cut_transfers';

    protected $fillable = [
        'uuid',
        'transfer_code',
        'productive_activity_id',
        'source_fund_id',
        'cut_account_number',
        'amount',
        'transfer_date',
        'bank_operation_number',
        'status',
        'requested_by_user_id',
        'authorized_by_user_id',
        'notes',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'transfer_date' => 'date',
    ];

    protected static function booted(): void {
        static::creating(function (RdrCutTransfer $cut) {
            if (empty($cut->uuid)) {
                $cut->uuid = (string) Str::uuid();
            }
            if (empty($cut->transfer_code)) {
                $year = date('Y', strtotime($cut->transfer_date ?? now()));
                $count = static::whereYear('transfer_date', $year)->count() + 1;
                $cut->transfer_code = sprintf('CUT-%s-%04d', $year, $count);
            }
        });
    }

    public function activity(): BelongsTo {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function sourceFund(): BelongsTo {
        return $this->belongsTo(FundSource::class, 'source_fund_id');
    }

    public function requestedByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function authorizedByUser(): BelongsTo {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }
}
