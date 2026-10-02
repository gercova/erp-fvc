<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailQuote extends Model
{
    use HasFactory;
    protected $table        = 'detail_quotes';
    protected $primaryKey   = 'id';
    protected $fillable     =
    [
        'idcotizacion',
        'idproducto',
        'cantidad',
        'precio_unitario',
        'precio_total',
        'idalmacen'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_total' => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class, 'idcotizacion');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'idproducto');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }
}
