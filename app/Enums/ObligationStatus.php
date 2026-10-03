<?php

namespace App\Enums;

enum ObligationStatus: string
{
    case PENDING     = 'PENDING';     // Pendiente de iniciar
    case IN_PROGRESS = 'IN_PROGRESS'; // En ejecución
    case COMPLETED   = 'COMPLETED';   // Cumplida y validada con evidencia
    case OVERDUE     = 'OVERDUE';     // Vencida sin cumplimiento

    public function label(): string
    {
        return match ($this) {
            self::PENDING     => 'Pendiente',
            self::IN_PROGRESS => 'En Progreso',
            self::COMPLETED   => 'Cumplida',
            self::OVERDUE     => 'Vencida',
        };
    }
}
