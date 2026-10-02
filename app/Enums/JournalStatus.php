<?php

namespace App\Enums;

enum JournalStatus: string
{
    case DRAFT = 'DRAFT';
    case POSTED = 'POSTED';
    case REVERSED = 'REVERSED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::POSTED => 'Asentado',
            self::REVERSED => 'Extornado',
        };
    }
}
