<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductiveUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'productive_units';

    protected $fillable = [
        'uuid',
        'productive_activity_id',
        'code',
        'name',
        'in_charge_user_id',
        'status',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductiveUnit $unit) {
            if (empty($unit->uuid)) {
                $unit->uuid = (string) Str::uuid();
            }
        });
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProductiveActivity::class, 'productive_activity_id');
    }

    public function inChargeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'in_charge_user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ActivityTransaction::class, 'productive_unit_id');
    }
}
