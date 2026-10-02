<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingRule extends Model
{
    use HasFactory;

    protected $table = 'accounting_rules';

    protected $fillable = [
        'event_code',
        'description',
        'source_type',
        'document_type_code',
        'payment_method_id',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority'  => 'integer',
    ];

    public function ruleLines(): HasMany
    {
        return $this->hasMany(AccountingRuleLine::class, 'accounting_rule_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PayMode::class, 'payment_method_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $eventCode): Builder
    {
        return $query->where('event_code', $eventCode);
    }
}
