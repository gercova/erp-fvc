<?php

namespace App\Models;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'code',
        'name',
        'element',
        'level',
        'nature',
        'classification',
        'accepts_movements',
        'allows_movement',
        'requires_third_party',
        'requires_cost_center',
        'requires_fund_source',
        'currency',
        'parent_id',
        'active',
        'is_active',
    ];

    protected $casts = [
        'element'              => 'integer',
        'level'                => 'integer',
        'nature'               => AccountNature::class,
        'classification'       => AccountType::class,
        'accepts_movements'    => 'boolean',
        'allows_movement'      => 'boolean',
        'requires_third_party' => 'boolean',
        'requires_cost_center' => 'boolean',
        'requires_fund_source' => 'boolean',
        'active'               => 'boolean',
        'is_active'            => 'boolean',
    ];

    public static function booted(): void {
        static::saving(function (ChartOfAccount $account) {
            // Keep aliases synchronized
            if ($account->isDirty('accepts_movements') && !$account->isDirty('allows_movement')) {
                $account->allows_movement = (bool) $account->accepts_movements;
            } elseif ($account->isDirty('allows_movement') && !$account->isDirty('accepts_movements')) {
                $account->accepts_movements = (bool) $account->allows_movement;
            }

            if ($account->isDirty('active') && !$account->isDirty('is_active')) {
                $account->is_active = (bool) $account->active;
            } elseif ($account->isDirty('is_active') && !$account->isDirty('active')) {
                $account->active = (bool) $account->is_active;
            }
        });
    }

    public function parent(): BelongsTo {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany {
        return $this->hasMany(ChartOfAccount::class, 'parent_id')->orderBy('code');
    }

    public function lines(): HasMany {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function scopeActive(Builder $query): Builder {
        return $query->where('active', true);
    }

    public function scopeMovement(Builder $query): Builder {
        return $query->where(function ($q) {
            $q->where('accepts_movements', true)
              ->orWhere('allows_movement', true);
        });
    }

    public function scopeForElement(Builder $query, int $element): Builder {
        return $query->where('element', $element);
    }
}
