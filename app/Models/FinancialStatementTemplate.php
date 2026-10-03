<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FinancialStatementTemplate extends Model
{
    use HasFactory;

    protected $table = 'financial_statement_templates';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'statement_type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FinancialStatementTemplate $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function lines(): HasMany  {
        return $this->hasMany(FinancialStatementTemplateLine::class, 'template_id')->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder {
        return $query->where('is_active', true);
    }

    public function scopeType(Builder $query, string $type): Builder {
        return $query->where('statement_type', $type);
    }
}
