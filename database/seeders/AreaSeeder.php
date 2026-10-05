<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use Illuminate\Support\Facades\DB;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        // Deshabilitar FK para poder truncar si se desea reiniciar
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Area::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $areas = [
            // NIVEL 1
            [
                'code'          => 'DG',
                'name'          => 'DIRECCION GENERAL',
                'parent_code'   => null,
                'type'          => 'DIRECCION',
                'level'         => 1,
                'is_advisory'   => false,
            ],
            // Consejos y secretarías de asesoría (nivel 2, dependen de DG)
            [
                'code'          => 'CA',
                'name'          => 'CONSEJO ASESOR',
                'parent_code'   => 'DG',
                'type'          => 'CONSEJO',
                'level'         => 2,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'CE',
                'name'          => 'CONSEJO ESTUDIANTIL',
                'parent_code'   => 'DG',
                'type'          => 'CONSEJO',
                'level'         => 2,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'SD',
                'name'          => 'SECRETARIA DE DIRECCION',
                'parent_code'   => 'DG',
                'type'          => 'SECRETARIA',
                'level'         => 2,
                'is_advisory'   => true,
            ],

            // NIVEL 2 – Áreas y unidades sustantivas
            [
                'code'          => 'AA',
                'name'          => 'AREA ADMINISTRATIVA',
                'parent_code'   => 'DG',
                'type'          => 'AREA',
                'level'         => 2,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'AP',
                'name'          => 'AREA DE PRODUCCION',
                'parent_code'   => 'DG',
                'type'          => 'AREA',
                'level'         => 2,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'SA',
                'name'          => 'SECRETARIA ACADEMICA',
                'parent_code'   => 'DG',
                'type'          => 'SECRETARIA',
                'level'         => 2,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'UA',
                'name'          => 'UNIDAD ACADEMICA',
                'parent_code'   => 'DG',
                'type'          => 'UNIDAD',
                'level'         => 2,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'AC',
                'name'          => 'AREA DE CALIDAD',
                'parent_code'   => 'DG',
                'type'          => 'AREA',
                'level'         => 2,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'UI',
                'name'          => 'UNIDAD DE INVESTIGACION',
                'parent_code'   => 'DG',
                'type'          => 'UNIDAD',
                'level'         => 2,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'UBE',
                'name'          => 'UNIDAD DE BIENESTAR Y EMPLEABILIDAD',
                'parent_code'   => 'DG',
                'type'          => 'UNIDAD',
                'level'         => 2,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'UFC',
                'name'          => 'UNIDAD DE FORMACION CONTINUA',
                'parent_code'   => 'DG',
                'type'          => 'UNIDAD',
                'level'         => 2,
                'is_advisory'   => true,
            ],

            // Dependencias de Área Administrativa
            [
                'code'          => 'II',
                'name'          => 'IMAGEN INSTITUCIONAL',
                'parent_code'   => 'AA',
                'type'          => 'OFICINA',
                'level'         => 3,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'ADM',
                'name'          => 'ADMINISTRACION',
                'parent_code'   => 'AA',
                'type'          => 'ADMINISTRACION',
                'level'         => 3,
                'is_advisory'   => false,
            ],

            // Dependencias de Unidad Académica
            [
                'code'          => 'SUA',
                'name'          => 'SECRETARIA DE UNIDAD ACADEMICA',
                'parent_code'   => 'UA',
                'type'          => 'SECRETARIA',
                'level'         => 3,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'DOC',
                'name'          => 'DOCENTES',
                'parent_code'   => 'UA',
                'type'          => 'DOCENTE',
                'level'         => 3,
                'is_advisory'   => false,
            ],

            // Coordinadores académicos (dependen de Secretaría de Unidad Académica)
            [
                'code'          => 'CAPA',
                'name'          => 'COORDINADOR ACADEMICO DE PRODUCCION AGROPECUARIA',
                'parent_code'   => 'SUA',
                'type'          => 'COORDINACION',
                'level'         => 4,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'CAET',
                'name'          => 'COORDINADOR ACADEMICO DE ENFERMERIA TECNICA',
                'parent_code'   => 'SUA',
                'type'          => 'COORDINACION',
                'level'         => 4,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'CAAIRC',
                'name'          => 'COORDINADOR ACADEMICO DE ADMINISTRACION DE REDES Y COMUNICACION',
                'parent_code'   => 'SUA',
                'type'          => 'COORDINACION',
                'level'         => 4,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'CAAA',
                'name'          => 'COORDINADOR ACADEMICO DE ASISTENCIA ADMINISTRATIVA',
                'parent_code'   => 'SUA',
                'type'          => 'COORDINACION',
                'level'         => 4,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'CAMF',
                'name'          => 'COORDINADOR ACADEMICO DE MANEJO FORESTAL',
                'parent_code'   => 'SUA',
                'type'          => 'COORDINACION',
                'level'         => 4,
                'is_advisory'   => false,
            ],

            // Subáreas de Docentes
            [
                'code'          => 'ESP',
                'name'          => 'ESPECIALIDAD',
                'parent_code'   => 'DOC',
                'type'          => 'AREA',
                'level'         => 4,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'EMP',
                'name'          => 'EMPLEABILIDAD',
                'parent_code'   => 'DOC',
                'type'          => 'AREA',
                'level'         => 4,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'EST',
                'name'          => 'ESTUDIANTES',
                'parent_code'   => 'DOC',
                'type'          => 'AREA',
                'level'         => 5,
                'is_advisory'   => false,
            ],

            // Dependencias de Administración
            [
                'code'          => 'ASAD',
                'name'          => 'ASISTENTE ADMINISTRATIVO',
                'parent_code'   => 'ADM',
                'type'          => 'PERSONAL',
                'level'         => 4,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'TES',
                'name'          => 'TESORERO',
                'parent_code'   => 'ADM',
                'type'          => 'PERSONAL',
                'level'         => 4,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'APAT',
                'name'          => 'AREA DE PATRIMONIO',
                'parent_code'   => 'ADM',
                'type'          => 'AREA',
                'level'         => 4,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'AAB',
                'name'          => 'AREA DE ABASTECIMIENTO',
                'parent_code'   => 'ADM',
                'type'          => 'AREA',
                'level'         => 4,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'GUA',
                'name'          => 'GUARDIANIA DIURNA',
                'parent_code'   => 'ADM',
                'type'          => 'PERSONAL',
                'level'         => 4,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'ASC',
                'name'          => 'ASISTENTE DE CAMPO',
                'parent_code'   => 'ADM',
                'type'          => 'PERSONAL',
                'level'         => 4,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'PEC',
                'name'          => 'PERSONAL DE CAMPO',
                'parent_code'   => 'ADM',
                'type'          => 'PERSONAL',
                'level'         => 4,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'PS',
                'name'          => 'PERSONAL DE SERVICIO',
                'parent_code'   => 'ADM',
                'type'          => 'PERSONAL',
                'level'         => 4,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'PS2',
                'name'          => 'PERSONAL DE SERVICIO II',
                'parent_code'   => 'ADM',
                'type'          => 'PERSONAL',
                'level'         => 4,
                'is_advisory'   => false,
            ],
            [
                'code'          => 'PS3',
                'name'          => 'PERSONAL DE SERVICIO III',
                'parent_code'   => 'ADM',
                'type'          => 'PERSONAL',
                'level'         => 4,
                'is_advisory'   => false,
            ],

            // Dependencias de Unidad de Bienestar y Empleabilidad
            [
                'code'          => 'SM',
                'name'          => 'SERVICIO MEDICO (TOPICO)',
                'parent_code'   => 'UBE',
                'type'          => 'SERVICIO',
                'level'         => 3,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'SPS',
                'name'          => 'SERVICIO PSICOPEDAGOGICO',
                'parent_code'   => 'UBE',
                'type'          => 'SERVICIO',
                'level'         => 3,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'SBS',
                'name'          => 'SERVICIO DE BIENESTAR SOCIAL (CONSEJERIA)',
                'parent_code'   => 'UBE',
                'type'          => 'SERVICIO',
                'level'         => 3,
                'is_advisory'   => true,
            ],
            [
                'code'          => 'SEM',
                'name'          => 'SERVICIO DE EMPLEABILIDAD',
                'parent_code'   => 'UBE',
                'type'          => 'SERVICIO',
                'level'         => 3,
                'is_advisory'   => true,
            ],
        ];

        foreach ($areas as $data) {
            $parent = $data['parent_code']
                ? Area::where('code', $data['parent_code'])->first()
                : null;

            Area::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name'          => $data['name'],
                    'parent_id'     => $parent?->id,
                    'type'          => $data['type'],
                    'level'         => $data['level'],
                    'is_advisory'   => $data['is_advisory'],
                    'head_user_id'  => null,
                ]
            );
        }
    }
}