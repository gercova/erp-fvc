<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Provider extends Model
{
    use HasFactory;

    protected $table = 'providers';

    protected $primaryKey = 'id';

    protected $fillable = [
        'iddoc',
        'nro_documento',
        'nombres',
        'direccion',
        'codigo_pais',
        'ubigeo',
        'telefono',
        'email',
    ];

    protected $appends = [
        'idubigeo',
        'correo',
    ];

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(IdentityDocumentType::class, 'iddoc');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'ubigeo', 'codigo');
    }

    public function getDocumentoCompletoAttribute(): string
    {
        $tipo = $this->tipoDocumento?->descripcion ?? 'DOC';
        return "{$tipo}: {$this->nro_documento}";
    }

    public function getIdubigeoAttribute(): ?string
    {
        return $this->ubigeo;
    }

    public function setIdubigeoAttribute(?string $value): void
    {
        $this->attributes['ubigeo'] = $value;
    }

    public function getCorreoAttribute(): ?string
    {
        return $this->email;
    }

    public function setCorreoAttribute(?string $value): void
    {
        $this->attributes['email'] = $value;
    }
}
