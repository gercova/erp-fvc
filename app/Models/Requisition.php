<?php

namespace App\Models;

use App\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requisition extends Model
{
    use SoftDeletes, HasApprovals;

    protected $fillable = [
        'correlativo',
        'user_id',
        'area_id',
        'dirigido_a',
        'de',
        'cargo',
        'fecha',
        'finalidad',
        'fuente_financiamiento',
        'fuente_especificar',
        'justificacion',
        'condiciones_admin',
        'total',
        'status',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }
}
