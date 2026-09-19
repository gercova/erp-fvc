<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseDeclaration extends Model
{
    use SoftDeletes, HasApprovals;

    protected $fillable = [
        'correlativo',
        'user_id',
        'area_id',
        'servidor_nombres',
        'dni',
        'cargo',
        'total',
        'total_letras',
        'conceptos',
        'fecha',
        'lugar',
        'certifico',
        'status',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ExpenseDeclarationItem::class);
    }
}
