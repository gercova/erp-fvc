<?php

namespace App\Enums;

enum AgreementType: string
{
    case FRAMEWORK = 'FRAMEWORK'; // Convenio Marco
    case SPECIFIC  = 'SPECIFIC';  // Convenio Específico

    public function label(): string
    {
        return match ($this) {
            self::FRAMEWORK => 'Convenio Marco',
            self::SPECIFIC  => 'Convenio Específico',
        };
    }
}
