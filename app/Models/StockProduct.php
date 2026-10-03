<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockProduct extends Model
{
    use HasFactory;

    protected $table = 'stock_products';

    protected $primaryKey = 'id';

    protected $fillable = [
        'idproducto',
        'idalmacen',
        'stock_minimo',
        'stock_actual',
        'precio_compra',
        'precio_venta',
        'fecha_registro',
        'stock_entrada',
    ];

    protected $casts = [
        'stock_minimo'      => 'integer',
        'stock_actual'      => 'integer',
        'stock_entrada'     => 'integer',
        'precio_compra'     => 'decimal:2',
        'precio_venta'      => 'decimal:2',
        'fecha_registro'    => 'date',
    ];

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class, 'idproducto');
    }

    public function warehouse(): BelongsTo {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }
}
