<?php

namespace App\Enums;

enum AccountType: string
{
    case ACTIVO             = 'ACTIVO';
    case PASIVO             = 'PASIVO';
    case PATRIMONIO         = 'PATRIMONIO';
    case GASTOS_NATURALEZA  = 'GASTOS_NATURALEZA';
    case INGRESOS           = 'INGRESOS';
    case COSTOS_PRODUCCION  = 'COSTOS_PRODUCCION';
    case GASTOS_FUNCION     = 'GASTOS_FUNCION';
    case ORDEN              = 'ORDEN';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO            => 'Activo',
            self::PASIVO            => 'Pasivo',
            self::PATRIMONIO        => 'Patrimonio Neto',
            self::GASTOS_NATURALEZA => 'Gastos por Naturaleza (Clase 6)',
            self::INGRESOS          => 'Ingresos (Clase 7)',
            self::COSTOS_PRODUCCION => 'Costos de Producción (Clase 9)',
            self::GASTOS_FUNCION    => 'Gastos por Función (Clase 9)',
            self::ORDEN             => 'Cuentas de Orden (Clase 0)',
        };
    }

    public static function fromElement(int $element): self
    {
        return match ($element) {
            1, 2, 3 => self::ACTIVO,
            4 => self::PASIVO,
            5 => self::PATRIMONIO,
            6 => self::GASTOS_NATURALEZA,
            7 => self::INGRESOS,
            8 => self::GASTOS_FUNCION,
            9 => self::COSTOS_PRODUCCION,
            0 => self::ORDEN,
            default => self::ACTIVO,
        };
    }
}
