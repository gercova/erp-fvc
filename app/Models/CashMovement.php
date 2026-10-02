<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashMovement extends Model
{
    use HasFactory;

    protected $table = 'cash_movements';

    protected $fillable = [
        'idarqueocaja',
        'idusuario',
        'tipo',
        'monto',
        'motivo',
        'fecha',
        'hora',
        'estado',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
        'estado' => 'integer',
    ];

    public function archingCash()
    {
        return $this->belongsTo(ArchingCash::class, 'idarqueocaja');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'idusuario');
    }
}
