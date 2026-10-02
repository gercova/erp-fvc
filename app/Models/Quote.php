<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function client()
    {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function details()
    {
        return $this->hasMany(DetailQuote::class, 'idcotizacion');
    }

    public function detailQuotes()
    {
        return $this->hasMany(DetailQuote::class, 'idcotizacion');
    }

    public function payMode()
    {
        return $this->belongsTo(PayMode::class, 'idpago');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario');
    }
}
