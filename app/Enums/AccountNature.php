<?php

namespace App\Enums;

enum AccountNature: string
{
    case DEBIT = 'DEBIT';
    case CREDIT = 'CREDIT';

    public function label(): string
    {
        return match ($this) {
            self::DEBIT => 'Deudora',
            self::CREDIT => 'Acreedora',
        };
    }
}
