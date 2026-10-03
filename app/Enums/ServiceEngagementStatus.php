<?php

namespace App\Enums;

enum ServiceEngagementStatus: string
{
    case DRAFT       = 'DRAFT';       // Borrador / Registro inicial
    case IN_PROGRESS = 'IN_PROGRESS'; // En ejecución
    case COMPLETED   = 'COMPLETED';   // Concluido con conformidad
    case CANCELLED   = 'CANCELLED';   // Cancelado / Rescindido

    public function label(): string
    {
        return match ($this) {
            self::DRAFT       => 'Borrador',
            self::IN_PROGRESS => 'En Ejecución',
            self::COMPLETED   => 'Completado',
            self::CANCELLED   => 'Cancelado',
        };
    }
}
