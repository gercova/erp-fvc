<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseDeclarationItem extends Model
{
    protected $fillable = [
        'expense_declaration_id',
        'fecha',
        'detalle_gestion',
        'importe',
    ];

    protected $casts = [
        'fecha' => 'date',
        'importe' => 'decimal:2',
    ];

    public function expenseDeclaration(): BelongsTo
    {
        return $this->belongsTo(ExpenseDeclaration::class);
    }
}
