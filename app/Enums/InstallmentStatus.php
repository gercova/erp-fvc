<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case PENDING   = 'PENDING';   // Pendiente en cronograma
    case SCHEDULED = 'SCHEDULED'; // Programada en cronograma (alias compat)
    case INVOICED  = 'INVOICED';  // Comprobante emitido (Billing / SaleNote)
    case COLLECTED = 'COLLECTED'; // Cobrada / recaudada en tesorería
    case PAID      = 'PAID';      // Pagada (alias compat)
    case OVERDUE   = 'OVERDUE';   // Vencida sin facturar o cobrar
    case CANCELLED = 'CANCELLED'; // Anulada por adenda o rescisión

    public function label(): string
    {
        return match ($this) {
            self::PENDING, self::SCHEDULED => 'Pendiente',
            self::INVOICED                 => 'Facturada',
            self::COLLECTED, self::PAID    => 'Cobrada',
            self::OVERDUE                  => 'Vencida',
            self::CANCELLED                => 'Cancelada',
        };
    }
}
