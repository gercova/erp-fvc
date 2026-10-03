<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ActivityTransactionCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'activity_transaction_categories';

    protected $fillable = [
        'uuid',
        'parent_id',
        'code',
        'name',
        'type',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void {
        static::creating(function (ActivityTransactionCategory $cat) {
            if (empty($cat->uuid)) {
                $cat->uuid = (string) Str::uuid();
            }
        });
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(ActivityTransactionCategory::class, 'parent_id');
    }

    public function children(): HasMany {
        return $this->hasMany(ActivityTransactionCategory::class, 'parent_id');
    }

    public function transactions(): HasMany {
        return $this->hasMany(ActivityTransaction::class, 'category_id');
    }
}
