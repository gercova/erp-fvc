<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleNote extends Model
{
    use HasFactory;
    protected $table        = 'sale_notes';
    protected $primaryKey   = 'id';
    protected $fillable     = [
        'idtipo_comprobante',
        'serie',
        'correlativo',
        'fecha_emision',
        'fecha_vencimiento',
        'hora',
        'idcliente',
        'modo_pago',
        'subtotal',
        'igv',
        'total',
        'monto_credito',
        'cuotas',
        'payment_breakdown',
        'observaciones',
        'estado',
        'idusuario',
        'idarqueocaja',
        'idfactura_anular',
        'billing_id',
        'vuelto',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'igv'           => 'decimal:2',
        'total'         => 'decimal:2',
        'monto_credito' => 'decimal:2',
        'vuelto'        => 'decimal:2',
        'cuotas'        => 'array',
        'payment_breakdown' => 'array',
    ];

    public function usuario(): BelongsTo {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function pago(): BelongsTo {
        return $this->belongsTo(PayMode::class, 'idpago');
    }

    public function cliente(): BelongsTo {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function billing(): BelongsTo {
        return $this->belongsTo(Billing::class, 'billing_id');
    }

    public function isConverted(): bool {
        return !is_null($this->billing_id) || (int) $this->estado === 2;
    }

    public function isAnnulled(): bool {
        return (int) $this->estado === 0;
    }

    public function details(): HasMany {
        return $this->hasMany(DetailSaleNote::class, 'idnotaventa');
    }

    public function detailPayments(): HasMany {
        return $this->hasMany(DetailPayment::class, 'idfactura')
            ->where(function ($q) {
                $q->where('idtipo_comprobante', $this->idtipo_comprobante ?: 2)
                    ->orWhere('idtipo_comprobante', 2);
            });
    }

    public function typeDocument(): BelongsTo {
        return $this->belongsTo(TypeDocument::class, 'idtipo_comprobante');
    }

    public function tipoDocumento(): BelongsTo {
        return $this->belongsTo(TypeDocument::class, 'idtipo_comprobante');
    }
}
