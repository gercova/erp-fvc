<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $fillable = [
        'iddoc',
        'nro_documento',
        'nombres',
        'direccion',
        'codigo_pais',
        'ubigeo',
        'telefono',
        'email'
    ];

    public function tipoDocumento()
    {
        return $this->belongsTo(IdentityDocumentType::class, 'iddoc');
    }
}
