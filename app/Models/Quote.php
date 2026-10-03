<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;
    protected $table        = 'quotes';
    protected $primaryKey   = 'id';
    protected $fillable     = [
        'serie',
        'correlativo',
        'fecha_emision',
        'fecha_vencimiento',
        'hora',
        'idcliente',
        'idpago',
        'subtotal',
        'igv',
        'total',
        'observaciones',
        'estado',
        'idusuario',
        'idcaja',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'igv' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function cliente(): BelongsTo {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function details(): HasMany {
        return $this->hasMany(DetailQuote::class, 'idcotizacion');
    }

    public function detailQuotes(): HasMany {
        return $this->hasMany(DetailQuote::class, 'idcotizacion');
    }

    public function payMode(): BelongsTo {
        return $this->belongsTo(PayMode::class, 'idpago');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, 'idusuario');
    }
}
