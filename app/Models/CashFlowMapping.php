<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlowMapping extends Model
{
    use HasFactory;

    protected $table = 'cash_flow_mappings';

    protected $fillable = [
        'category',
        'flow_type',
        'concept_name',
        'account_prefix',
        'event_key',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeOperating(Builder $query): Builder
    {
        return $query->where('category', 'OPERATING');
    }

    public function scopeInvesting(Builder $query): Builder
    {
        return $query->where('category', 'INVESTING');
    }

    public function scopeFinancing(Builder $query): Builder
    {
        return $query->where('category', 'FINANCING');
    }
}
