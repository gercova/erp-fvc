<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPayable extends Model
{
    use HasFactory;

    protected $table = 'accounts_payable';

    protected $fillable = [
        'idcompra',
        'idproveedor',
        'idalmacen',
        'monto_total',
        'monto_pagado',
        'saldo',
        'fecha_emision',
        'fecha_vencimiento',
        'estado',
        'cuotas',
        'observaciones',
        'idusuario',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo' => 'decimal:2',
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'cuotas' => 'array',
    ];

    public function buy(): BelongsTo
    {
        return $this->belongsTo(Buy::class, 'idcompra');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'idproveedor');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idusuario');
    }
}
