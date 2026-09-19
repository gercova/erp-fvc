<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // Nivel 1
        $direccionGeneral = Area::create([
            'code' => 'DG', 'name' => 'Dirección General', 'type' => 'direccion_general', 'level' => 1,
        ]);

        Area::create([
            'code' => 'CONS-ASESOR', 'name' => 'Consejo Asesor', 'type' => 'organo_consultivo',
            'parent_id' => $direccionGeneral->id, 'level' => 1, 'is_advisory' => true,
        ]);

        Area::create([
            'code' => 'CONS-ESTUD', 'name' => 'Concejo Estudiantil', 'type' => 'organo_consultivo',
            'parent_id' => $direccionGeneral->id, 'level' => 1, 'is_advisory' => true,
        ]);

        // Nivel 2
        Area::create([
            'code' => 'SEC-DIR', 'name' => 'Secretaría de Dirección', 'type' => 'area',
            'parent_id' => $direccionGeneral->id, 'level' => 2, 'is_advisory' => true,
        ]);

        $areaAdmin = Area::create([
            'code' => 'ADM', 'name' => 'Área Administrativa', 'type' => 'area',
            'parent_id' => $direccionGeneral->id, 'level' => 2,
        ]);

        Area::create([
            'code' => 'PROD', 'name' => 'Área de Producción', 'type' => 'area',
            'parent_id' => $direccionGeneral->id, 'level' => 2,
        ]);

        Area::create([
            'code' => 'SEC-ACAD', 'name' => 'Secretaría Académica', 'type' => 'area',
            'parent_id' => $direccionGeneral->id, 'level' => 2,
        ]);

        $unidadAcademica = Area::create([
            'code' => 'UA', 'name' => 'Unidad Académica', 'type' => 'unidad',
            'parent_id' => $direccionGeneral->id, 'level' => 2,
        ]);

        Area::create([
            'code' => 'CALIDAD', 'name' => 'Área de Calidad', 'type' => 'area',
            'parent_id' => $direccionGeneral->id, 'level' => 2,
        ]);

        Area::create([
            'code' => 'INVEST', 'name' => 'Unidad de Investigación', 'type' => 'unidad',
            'parent_id' => $direccionGeneral->id, 'level' => 2,
        ]);

        $bienestar = Area::create([
            'code' => 'BIENESTAR', 'name' => 'Unidad de Bienestar y Empleabilidad', 'type' => 'unidad',
            'parent_id' => $direccionGeneral->id, 'level' => 2,
        ]);

        Area::create([
            'code' => 'FORM-CONT', 'name' => 'Unidad de Formación Continua', 'type' => 'unidad',
            'parent_id' => $direccionGeneral->id, 'level' => 2,
        ]);

        // Bajo Área Administrativa (nivel 3, línea de coordinación punteada)
        Area::create([
            'code' => 'IMAGEN', 'name' => 'Imagen Institucional', 'type' => 'area',
            'parent_id' => $areaAdmin->id, 'level' => 3, 'is_advisory' => true,
        ]);
        $administracion = Area::create([
            'code' => 'ADMINISTRACION', 'name' => 'Administración', 'type' => 'area',
            'parent_id' => $areaAdmin->id, 'level' => 3,
        ]);

        foreach ([
            'ASIST-ADM' => 'Asistente Administrativo',
            'TESORERIA' => 'Tesorero',
            'PATRIMONIO' => 'Área de Patrimonio',
            'ABASTECIMIENTO' => 'Área de Abastecimiento',
            'GUARDIANIA' => 'Guardianía Diurna',
            'ASIST-CAMPO' => 'Asistente de Campo',
            'PERS-CAMPO' => 'Personal de Campo',
            'PERS-SERV-1' => 'Personal de Servicio',
            'PERS-SERV-2' => 'Personal de Servicio II',
            'PERS-SERV-3' => 'Personal de Servicio III',
        ] as $code => $name) {
            Area::create([
                'code' => $code, 'name' => $name, 'type' => 'area',
                'parent_id' => $administracion->id, 'level' => 4,
            ]);
        }

        // Bajo Unidad Académica: coordinaciones de programa (nivel 3)
        foreach ([
            'PROG-AGRO' => 'Coordinación Académica de Producción Agropecuaria',
            'PROG-ENF' => 'Coordinación Académica de Enfermería Técnica',
            'PROG-REDES' => 'Coordinación Académica de Administración de Redes y Comunicación',
            'PROG-ASIST-ADM' => 'Coordinación Académica de Asistencia Administrativa',
            'PROG-FOREST' => 'Coordinación Académica de Manejo Forestal',
        ] as $code => $name) {
            Area::create([
                'code' => $code, 'name' => $name, 'type' => 'programa_academico',
                'parent_id' => $unidadAcademica->id, 'level' => 3,
            ]);
        }

        // Bajo Unidad de Bienestar y Empleabilidad (nivel 3, línea de coordinación punteada)
        foreach ([
            'SERV-MEDICO' => 'Servicio Médico (Tópico)',
            'SERV-PSICOPEDAGOGICO' => 'Servicio Psicopedagógico',
            'SERV-BIENESTAR' => 'Servicio de Bienestar Social (Consejería)',
            'SERV-EMPLEABILIDAD' => 'Servicio de Empleabilidad',
        ] as $code => $name) {
            Area::create([
                'code' => $code, 'name' => $name, 'type' => 'area',
                'parent_id' => $bienestar->id, 'level' => 3, 'is_advisory' => true,
            ]);
        }
    }
}