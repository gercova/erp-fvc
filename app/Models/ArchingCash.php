<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchingCash extends Model
{
    use HasFactory;

    protected $table = 'arching_cashes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'idcaja',
        'idusuario',
        'idalmacen',
        'fecha_inicio',
        'fecha_fin',
        'monto_inicial',
        'monto_final',
        'total_ventas',
        'estado',
        'monto_estimado',
        'diferencia',
        'total_ingresos',
        'total_egresos',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'monto_inicial' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'monto_estimado' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_egresos' => 'decimal:2',
    ];

    public function cash()
    {
        return $this->belongsTo(Cash::class, 'idcaja');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function movements()
    {
        return $this->hasMany(CashMovement::class, 'idarqueocaja');
    }

    public function payments()
    {
        return $this->hasMany(DetailPayment::class, 'idarqueocaja');
    }

    public function saleNotes()
    {
        return $this->hasMany(SaleNote::class, 'idarqueocaja');
    }

    public function billings()
    {
        return $this->hasMany(Billing::class, 'idarqueocaja');
    }
}
