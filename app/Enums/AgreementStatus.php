<?php

namespace App\Enums;

enum AgreementStatus: string
{
    case DRAFT          = 'DRAFT';          // Formulación inicial
    case IN_APPROVAL    = 'IN_APPROVAL';    // En revisión / firmas jerárquicas
    case ACTIVE         = 'ACTIVE';         // Vigente y en ejecución
    case EXPIRING_SOON  = 'EXPIRING_SOON';  // Próximo a vencer (<= 30 días)
    case EXPIRED        = 'EXPIRED';        // Plazo concluido sin finiquito
    case SETTLED        = 'SETTLED';        // Liquidado formalmente con acta
    case TERMINATED     = 'TERMINATED';     // Rescindido / cancelado unilateralmente
    case REJECTED       = 'REJECTED';       // Rechazado en cadena de aprobación

    public function label(): string
    {
        return match ($this) {
            self::DRAFT         => 'Borrador',
            self::IN_APPROVAL   => 'En Aprobación',
            self::ACTIVE        => 'Vigente',
            self::EXPIRING_SOON => 'Por Vencer',
            self::EXPIRED       => 'Vencido',
            self::SETTLED       => 'Liquidado',
            self::TERMINATED    => 'Rescindido',
            self::REJECTED      => 'Rechazado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT         => 'secondary',
            self::IN_APPROVAL   => 'info',
            self::ACTIVE        => 'success',
            self::EXPIRING_SOON => 'warning',
            self::EXPIRED       => 'danger',
            self::SETTLED       => 'primary',
            self::TERMINATED    => 'dark',
            self::REJECTED      => 'danger',
        };
    }
}
