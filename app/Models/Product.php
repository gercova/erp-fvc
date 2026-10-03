<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $primaryKey = 'id';

    protected $fillable = [
        'codigo_interno',
        'codigo_barras',
        'codigo_sunat',
        'descripcion',
        'idunidad',
        'idcategoria',
        'igv',
        'idcodigo_igv',
        'precio_compra',
        'precio_venta',
        'opcion',
        'stock_actual',
    ];

    public function unit(): BelongsTo {
        return $this->belongsTo(Unit::class, 'idunidad');
    }

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class, 'idcategoria');
    }

    public function igvTypeAffection(): BelongsTo {
        return $this->belongsTo(IgvTypeAffection::class, 'idcodigo_igv');
    }

    public function stockProducts(): HasMany {
        return $this->hasMany(StockProduct::class, 'idproducto');
    }

    public function isService(): bool {
        return (int) $this->opcion === 2;
    }

    public function isProduct(): bool
    {
        return (int) $this->opcion === 1;
    }
}
