<?php

namespace App\Enums;

enum ServiceCategory: string
{
    case TRAINING             = 'TRAINING';             // Cursos, talleres, pasantías
    case TECHNICAL_ASSISTANCE = 'TECHNICAL_ASSISTANCE'; // Asistencia técnica de campo
    case CONSULTING           = 'CONSULTING';           // Consultoría especializada / peritajes
    case LABORATORY_ANALYSIS  = 'LABORATORY_ANALYSIS';  // Análisis de suelos, cacao, agua, café
    case OTHER                = 'OTHER';                // Otros servicios tecnológicos

    public function label(): string
    {
        return match ($this) {
            self::TRAINING             => 'Capacitación y Cursos',
            self::TECHNICAL_ASSISTANCE => 'Asistencia Técnica',
            self::CONSULTING           => 'Consultoría Especializada',
            self::LABORATORY_ANALYSIS  => 'Análisis de Laboratorio',
            self::OTHER                => 'Otros Servicios',
        };
    }
}
