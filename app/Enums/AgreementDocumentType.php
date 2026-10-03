<?php

namespace App\Enums;

enum AgreementDocumentType: string
{
    case SIGNED_AGREEMENT = 'SIGNED_AGREEMENT'; // Convenio suscrito / escaneado
    case RESOLUTION       = 'RESOLUTION';       // Resolución Directoral de aprobación
    case TECHNICAL_REPORT = 'TECHNICAL_REPORT'; // Informe técnico de seguimiento
    case SETTLEMENT_ACT   = 'SETTLEMENT_ACT';   // Acta de liquidación y finiquito
    case ADDENDUM_FILE    = 'ADDENDUM_FILE';    // Documento de adenda
    case OTHER            = 'OTHER';            // Otros antecedentes

    public function label(): string
    {
        return match ($this) {
            self::SIGNED_AGREEMENT => 'Convenio Suscrito',
            self::RESOLUTION       => 'Resolución Directoral',
            self::TECHNICAL_REPORT => 'Informe Técnico',
            self::SETTLEMENT_ACT   => 'Acta de Liquidación',
            self::ADDENDUM_FILE    => 'Adenda',
            self::OTHER            => 'Otro Documento',
        };
    }
}
