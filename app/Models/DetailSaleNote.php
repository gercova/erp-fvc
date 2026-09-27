<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailSaleNote extends Model
{
    use HasFactory;
    protected $table        = 'detail_sale_notes';
    protected $primaryKey   = 'id';
    protected $fillable     = 
    [
        'idnotaventa',
        'idproducto',
        'cantidad',
        'descuento',
        'igv',
        'precio_unitario',
        'precio_total',
        'opcion',
        'idalmacen',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'descuento' => 'decimal:2',
        'igv' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
    ];

    public function saleNote(): BelongsTo {
        return $this->belongsTo(SaleNote::class, 'idnotaventa');
    }

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class, 'idproducto');
    }
}
