<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Serie extends Model
{
    use HasFactory;
    protected $table        = 'series';
    protected $primaryKey   = 'id';
    protected $fillable     = 
    [
        'serie',
        'correlativo',
        'idtipo_documento',
        'idtipo_documento_relacionado',
        'idcaja',
        'idalmacen',
        'direccion',
        'estado'
    ];

    public function warehouse(): BelongsTo {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function typeDocument():BelongsTo {
        return $this->belongsTo(TypeDocument::class, 'idtipo_documento');
    }

    public function cash(): BelongsTo {
        return $this->belongsTo(Cash::class, 'idcaja');
    }
}
