<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id',
        'item_number',
        'cantidad',
        'descripcion',
        'precio_unitario',
        'precio_total',
    ];

    protected $casts = [
        'cantidad'          => 'decimal:2',
        'precio_unitario'   => 'decimal:2',
        'precio_total'      => 'decimal:2',
        'item_number'       => 'integer',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }
}
