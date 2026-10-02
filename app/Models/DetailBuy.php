<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailBuy extends Model
{
    use HasFactory;

    protected $table = 'detail_buys';

    protected $primaryKey = 'id';

    protected $fillable = [
        'idcompra',
        'idproducto',
        'cantidad',
        'descuento',
        'igv',
        'id_afectacion_igv',
        'precio_unitario',
        'precio_total',
        'idalmacen',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'descuento' => 'decimal:2',
        'igv' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
    ];

    public function buy(): BelongsTo
    {
        return $this->belongsTo(Buy::class, 'idcompra');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'idproducto');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }
}
