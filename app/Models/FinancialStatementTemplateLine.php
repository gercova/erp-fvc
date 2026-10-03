<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialStatementTemplateLine extends Model
{
    use HasFactory;

    protected $table = 'financial_statement_template_lines';

    protected $fillable = [
        'template_id',
        'line_code',
        'parent_line_id',
        'line_name',
        'line_type',
        'account_range',
        'polarity',
        'calculation_formula',
        'sort_order',
        'level',
        'is_bold',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'level'      => 'integer',
        'is_bold'    => 'boolean',
    ];

    public function template(): BelongsTo {
        return $this->belongsTo(FinancialStatementTemplate::class, 'template_id');
    }

    public function parentLine(): BelongsTo {
        return $this->belongsTo(self::class, 'parent_line_id');
    }

    public function childLines(): HasMany {
        return $this->hasMany(self::class, 'parent_line_id')->orderBy('sort_order');
    }

    public function isHeader(): bool {
        return $this->line_type === 'HEADER';
    }

    public function isSubtotal(): bool {
        return $this->line_type === 'SUBTOTAL';
    }

    public function isTotal(): bool {
        return $this->line_type === 'TOTAL';
    }

    public function isItem(): bool {
        return $this->line_type === 'ITEM';
    }
}
