<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Billing extends Model
{
    use HasFactory;

    protected $table = 'billings';

    protected $fillable = [
        'idtipo_comprobante',
        'serie',
        'correlativo',
        'fecha_emision',
        'fecha_vencimiento',
        'hora',
        'idcliente',
        'idmoneda',
        'idpago',
        'modo_pago',
        'sunat_forma_pago',
        'exonerada',
        'inafecta',
        'gravada',
        'anticipo',
        'igv',
        'icbper',
        'gratuita',
        'otros_cargos',
        'total',
        'monto_credito',
        'cuotas',
        'payment_breakdown',
        'observaciones',
        'cdr',
        'anulado',
        'id_tipo_nota_credito',
        'id_tipo_nota_debito',
        'idfactura_anular',
        'motivo',
        'estado_cpe',
        'errores',
        'nticket',
        'sale_note_id',
        'idusuario',
        'idarqueocaja',
        'vuelto',
        'qr',
        'idalmacen',
    ];


    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'exonerada' => 'decimal:2',
        'inafecta' => 'decimal:2',
        'gravada' => 'decimal:2',
        'anticipo' => 'decimal:2',
        'igv' => 'decimal:2',
        'icbper' => 'decimal:2',
        'gratuita' => 'decimal:2',
        'otros_cargos' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_credito' => 'decimal:2',
        'cuotas' => 'array',
        'payment_breakdown' => 'array',
        'anulado' => 'boolean',
        'vuelto' => 'decimal:2',
    ];

    public function saleNote(): BelongsTo {
        return $this->belongsTo(SaleNote::class, 'sale_note_id');
    }


    public function customer(): BelongsTo {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class, 'idcliente');
    }

    public function currency(): BelongsTo {
        return $this->belongsTo(Currency::class, 'idmoneda');
    }

    public function typeDocument(): BelongsTo {
        return $this->belongsTo(TypeDocument::class, 'idtipo_comprobante');
    }

    public function payMode(): BelongsTo {
        return $this->belongsTo(PayMode::class, 'idpago');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'idusuario');
    }

    public function archingCash(): BelongsTo {
        return $this->belongsTo(ArchingCash::class, 'idarqueocaja');
    }

    public function warehouse(): BelongsTo {
        return $this->belongsTo(Warehouse::class, 'idalmacen');
    }

    public function noteType(): BelongsTo {
        return $this->belongsTo(CreditNoteType::class, 'id_tipo_nota_credito');
    }

    public function creditNoteType(): BelongsTo {
        return $this->belongsTo(CreditNoteType::class, 'id_tipo_nota_credito');
    }

    public function debitNoteType(): BelongsTo {
        return $this->belongsTo(DebitNoteType::class, 'id_tipo_nota_debito');
    }

    public function parentBilling(): BelongsTo {
        return $this->belongsTo(self::class, 'idfactura_anular');
    }

    public function details(): HasMany {
        return $this->hasMany(DetailBilling::class, 'idfacturacion');
    }
}
