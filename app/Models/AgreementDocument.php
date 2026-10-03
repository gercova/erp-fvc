<?php

namespace App\Models;

use App\Enums\AgreementDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgreementDocument extends Model
{
    use HasFactory;

    protected $table = 'agreement_documents';

    protected $fillable = [
        'uuid',
        'agreement_id',
        'document_type',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'version',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'document_type' => AgreementDocumentType::class,
        'file_size'     => 'integer',
        'version'       => 'integer',
    ];

    protected static function booted(): void {
        static::creating(function (AgreementDocument $doc) {
            if (empty($doc->uuid)) {
                $doc->uuid = (string) Str::uuid();
            }
            if (empty($doc->file_name) && !empty($doc->file_path)) {
                $doc->file_name = basename($doc->file_path);
            }
        });
    }

    public function agreement(): BelongsTo {
        return $this->belongsTo(Agreement::class, 'agreement_id');
    }

    public function uploader(): BelongsTo {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
