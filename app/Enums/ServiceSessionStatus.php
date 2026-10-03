<?php

namespace App\Enums;

enum ServiceSessionStatus: string
{
    case SCHEDULED   = 'SCHEDULED';   // Programada
    case CONDUCTED   = 'CONDUCTED';   // Realizada
    case CANCELLED   = 'CANCELLED';   // Cancelada
    case RESCHEDULED = 'RESCHEDULED'; // Reprogramada

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED   => 'Programada',
            self::CONDUCTED   => 'Realizada',
            self::CANCELLED   => 'Cancelada',
            self::RESCHEDULED => 'Reprogramada',
        };
    }
}
