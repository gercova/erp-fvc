<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case SCHEDULED = 'SCHEDULED'; // Programada en cronograma
    case INVOICED  = 'INVOICED';  // Comprobante emitido (Billing / SaleNote)
    case PAID      = 'PAID';      // Cobrada / recaudada en tesorería
    case OVERDUE   = 'OVERDUE';   // Vencida sin facturar o pagar
    case CANCELLED = 'CANCELLED'; // Anulada por adenda o rescisión

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Programada',
            self::INVOICED  => 'Facturada',
            self::PAID      => 'Pagada',
            self::OVERDUE   => 'Vencida',
            self::CANCELLED => 'Cancelada',
        };
    }
}
