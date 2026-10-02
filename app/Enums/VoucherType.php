<?php

namespace App\Enums;

enum VoucherType: string
{
    case OPENING = 'OPENING';
    case OPERATING = 'OPERATING';
    case ADJUSTMENT = 'ADJUSTMENT';
    case CLOSING = 'CLOSING';
    case REVERSAL = 'REVERSAL';

    public function label(): string
    {
        return match ($this) {
            self::OPENING => 'Apertura',
            self::OPERATING => 'Operativo / Diario',
            self::ADJUSTMENT => 'Ajuste',
            self::CLOSING => 'Cierre',
            self::REVERSAL => 'Extorno / Reversión',
        };
    }
}
