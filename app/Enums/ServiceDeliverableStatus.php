<?php

namespace App\Enums;

enum ServiceDeliverableStatus: string
{
    case PENDING      = 'PENDING';      // Pendiente de elaboración
    case SUBMITTED    = 'SUBMITTED';    // Entregado por el responsable
    case UNDER_REVIEW = 'UNDER_REVIEW'; // En revisión técnica
    case APPROVED     = 'APPROVED';     // Aprobado con conformidad
    case REJECTED     = 'REJECTED';     // Observado / Rechazado

    public function label(): string
    {
        return match ($this) {
            self::PENDING      => 'Pendiente',
            self::SUBMITTED    => 'Presentado',
            self::UNDER_REVIEW => 'En Revisión',
            self::APPROVED     => 'Aprobado',
            self::REJECTED     => 'Observado',
        };
    }
}
