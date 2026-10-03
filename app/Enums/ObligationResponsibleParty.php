<?php

namespace App\Enums;

enum ObligationResponsibleParty: string
{
    case OUR_INSTITUTION = 'OUR_INSTITUTION';
    case COUNTERPARTY    = 'COUNTERPARTY';
    case MUTUAL          = 'MUTUAL';

    public function label(): string
    {
        return match ($this) {
            self::OUR_INSTITUTION => 'Nuestra Institución',
            self::COUNTERPARTY    => 'Contraparte',
            self::MUTUAL          => 'Obligación Mutua / Conjunta',
        };
    }
}
