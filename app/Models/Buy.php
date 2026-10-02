<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Buy extends Model
{
    use HasFactory;

    protected $table = 'buys';

    protected $primaryKey = 'id';

    protected $fillable = [
        'idtipo_comprobante',
        'serie',
        'correlativo',
        'fecha_emision',
        'fecha_vencimiento',
        'hora',
        'idproveedor',
        'idalmacen',
        'idmoneda',
        'idpago',
        'modo_pago',
        'condicion_pago',
        'monto_credito',
        'cuotas',
        'exonerada',
        'inafecta',
        'gravada',
        'anticipo',
        'igv',
        'gratuita',
        'otros_cargos',
        'total',
        'observaciones',
        'estado',
        'idusuario',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'cuotas' => 'array',
        'monto_credito' => 'decimal:2',
        'total' => 'decimal:2',
        'igv' => 'decimal:2',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'idproveedor');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function typeDocument(): BelongsTo
    {
        return $this->belongsTo(TypeDocument::class, 'idtipo_comprobante');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function detailBuys(): HasMany
    {
        return $this->hasMany(DetailBuy::class, 'idcompra');
    }

    public function accountPayable(): HasOne
    {
        return $this->hasOne(AccountPayable::class, 'idcompra');
    }
}
