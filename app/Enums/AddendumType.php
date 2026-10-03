<?php

namespace App\Enums;

enum AddendumType: string
{
    case TIME_EXTENSION       = 'TIME_EXTENSION';       // Prórroga de vigencia
    case AMOUNT_MODIFICATION  = 'AMOUNT_MODIFICATION';  // Modificación presupuestal (+/-)
    case SCOPE_CHANGE         = 'SCOPE_CHANGE';         // Modificación de metas u obligaciones
    case MIXED                = 'MIXED';                // Mixta (Plazo + Monto + Alcance)

    public function label(): string
    {
        return match ($this) {
            self::TIME_EXTENSION      => 'Prórroga de Plazo',
            self::AMOUNT_MODIFICATION => 'Modificación Presupuestal',
            self::SCOPE_CHANGE        => 'Cambio de Alcance',
            self::MIXED               => 'Adenda Mixta',
        };
    }
}
