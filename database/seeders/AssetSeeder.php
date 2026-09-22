<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        // 1. Asegurar área de Admisión (Screenshot 3)
        $areaAdmision = Area::firstOrCreate(
            ['code' => 'ADMISION'],
            [
                'name'  => 'Área de Admisión - CEPRE',
                'type'  => 'area',
                'level' => 2,
            ]
        );

        // 2. Área de Administración / Tesorería (Screenshot 2)
        $areaAdmin = Area::where('code', 'ADMINISTRACION')->first() ?? Area::where('code', 'ADM')->first();

        if ($areaAdmin) {
            $adminAssets = [
                ['orden' => 26, 'codigo_producto' => '74648390', 'codigo' => '003-10  01', 'descripcion' => 'SILLA FIJA DE METAL CON RUEDA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 30.00, 'condicion' => 'R', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2010', 'ubicacion' => 'ADMINISTRACION', 'observaciones' => 'RETIRADO ABAST. BAJA'],
                ['orden' => 27, 'codigo_producto' => '74648119', 'codigo' => '088-12  01', 'descripcion' => 'SILLA FIJA DE MADERA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 65.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2012', 'ubicacion' => 'ADMINISTRACION', 'custodio' => 'JHEYSER'],
                ['orden' => 28, 'codigo_producto' => '74648119', 'codigo' => '050-11  01', 'descripcion' => 'SILLA FIJA DE MADERA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 60.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2011', 'ubicacion' => 'ADMINISTRACION', 'observaciones' => 'POR REPARAR'],
                ['orden' => 29, 'codigo_producto' => '74648831', 'codigo' => '006-13  01', 'descripcion' => 'SILLON GIRATORIO (OTROS), TAPIZ DORADO', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2013', 'ubicacion' => 'ADMINISTRACION'],
                ['orden' => 30, 'codigo_producto' => '74646266', 'codigo' => '004-11  01', 'descripcion' => 'MOSTRADOR 2 PISOS, 4 COMP.', 'marca' => 'MADERA/VIDRIO', 'modelo' => 'RECTANGULAR', 'serie' => 'SIN SERIE', 'costo' => 500.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2011', 'ubicacion' => 'ADMINISTRACION'],
                ['orden' => 31, 'codigo_producto' => '74640592', 'codigo' => '001-11  01', 'descripcion' => 'ARMARIO DE MADERA, 2 PUERTAS', 'marca' => 'MADERA', 'modelo' => 'RECTANGULAR', 'serie' => 'SIN SERIE', 'costo' => 500.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2011', 'ubicacion' => 'ADMINISTRACION', 'custodio' => 'LEONIDAS'],
                ['orden' => 32, 'codigo_producto' => '74222726', 'codigo' => '001-15  01', 'descripcion' => 'FOTOCOPIADORA EN GENERAL', 'marca' => 'KONICA MINOLTA', 'modelo' => 'FK-503', 'serie' => '31125048', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2015', 'ubicacion' => 'ADMINISTRACION'],
                ['orden' => 33, 'codigo_producto' => '95228117', 'codigo' => '008-19  01', 'descripcion' => 'SWITCH PARA RED', 'marca' => 'TP-LINK', 'modelo' => 'TL-SG1048', 'serie' => '218C722001081', 'costo' => 677.97, 'condicion' => 'R', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2019', 'ubicacion' => 'ADMINISTRACION', 'observaciones' => 'INF:N°016-2024-ARMD-JUA-IESTP"FVC"-U'],
                ['orden' => 34, 'codigo_producto' => '74644491', 'codigo' => '007-23  01', 'descripcion' => 'GABINETE PEQUEÑO, NEGRO', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'M', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => null, 'ubicacion' => 'ADMINISTRACION'],
                ['orden' => 35, 'codigo_producto' => '74080026', 'codigo' => '001-21  01', 'descripcion' => 'ADAPTADOR INALAMBRICO PARA RED', 'marca' => 'TP-LINK', 'modelo' => 'TL-WN725N', 'serie' => '22150E9008062', 'costo' => 60.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2021', 'ubicacion' => 'ADMINISTRACION'],
                ['orden' => 36, 'codigo_producto' => '74229989', 'codigo' => '001-24  01', 'descripcion' => 'SURTIDOR DE AGUA ELECTRICO - DISPENSADOR ELEC', 'marca' => 'CONTINENTAL', 'modelo' => 'CE-WD131PE', 'serie' => 'SIN SERIE', 'costo' => 160.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2024', 'ubicacion' => 'ADMINISTRACION'],
                ['orden' => 37, 'codigo_producto' => '74644118', 'codigo' => '003-25  01', 'descripcion' => 'ESTANTE DE MELAMINE COLOR BLANCO', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 220.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2025', 'ubicacion' => 'ADMINISTRACION', 'custodio' => 'KARINA', 'observaciones' => 'FACT.E001-299'],
                ['orden' => 38, 'codigo_producto' => '74089950', 'codigo' => '87-25  001', 'descripcion' => 'UNIDAD CENTRAL DE PROCESO-CPU', 'marca' => 'CORE I5', 'modelo' => 'LGA1700', 'serie' => 'SIN SERIE', 'costo' => 700.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2025', 'ubicacion' => 'ADMINISTRACION', 'custodio' => 'KARINA', 'observaciones' => 'FACT.E001-177'],
                ['orden' => 39, 'codigo_producto' => '95225812', 'codigo' => '59-25  01', 'descripcion' => 'MONITOR LED, 19.5"', 'marca' => 'SAMSUNG', 'modelo' => 'IPSFHD22', 'serie' => 'SIN SERIE', 'costo' => 550.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2025', 'ubicacion' => 'ADMINISTRACION', 'custodio' => 'KARINA', 'observaciones' => 'FACT.E001-177'],
                ['orden' => 40, 'codigo_producto' => '74089500', 'codigo' => '064-25  01', 'descripcion' => 'TECLADO-KEYBOARD, NEGRO', 'marca' => 'LOGITECH', 'modelo' => 'MK120', 'serie' => 'SIN SERIE', 'costo' => 80.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2025', 'ubicacion' => 'ADMINISTRACION', 'custodio' => 'KARINA', 'observaciones' => 'FACT.E001-177'],
                ['orden' => 50, 'codigo_producto' => null, 'codigo' => '81-25  01', 'descripcion' => 'MOUSE', 'marca' => 'LOGITECH', 'modelo' => 'MK120', 'serie' => 'SIN SERIE', 'costo' => 80.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2025', 'ubicacion' => 'ADMINISTRACION', 'custodio' => 'KARINA', 'observaciones' => 'FACT.E001-177'],
                ['orden' => 51, 'codigo_producto' => '46225215', 'codigo' => '42-25  01', 'descripcion' => 'ESTABILIZADOR', 'marca' => 'POWER LITE', 'modelo' => '1000VA', 'serie' => 'SIN SERIE', 'costo' => 80.01, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2026', 'ubicacion' => 'ADMINISTRACION', 'custodio' => 'KARINA', 'observaciones' => 'FACT.E001-177'],
            ];

            foreach ($adminAssets as $item) {
                $item['area_id'] = $areaAdmin->id;
                $item['user_id'] = $admin?->id;
                Asset::firstOrCreate(
                    ['area_id' => $areaAdmin->id, 'orden' => $item['orden']],
                    $item
                );
            }
        }

        // Bienes de Admisión (Screenshot 3)
        $admisionAssets = [
            ['orden' => 1, 'codigo_producto' => null, 'codigo' => '07-22', 'descripcion' => 'LAPTOP', 'marca' => 'CORE i7', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => null, 'ubicacion' => 'ADMISION'],
            ['orden' => 2, 'codigo_producto' => '74222358', 'codigo' => '011-21', 'descripcion' => 'IMPRESORA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => null, 'ubicacion' => 'ADMISION'],
            ['orden' => 3, 'codigo_producto' => '74644932', 'codigo' => '005-17', 'descripcion' => 'MESA DE MADERA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => null, 'ubicacion' => 'ADMISION'],
            ['orden' => 4, 'codigo_producto' => '74644932', 'codigo' => '05-17', 'descripcion' => 'MESA DE MADERA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2017', 'ubicacion' => 'ADMISION'],
            ['orden' => 5, 'codigo_producto' => '74648119', 'codigo' => '047-11', 'descripcion' => 'SILLA FIJA DE MADERA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2011', 'ubicacion' => 'ADMISION'],
            ['orden' => 6, 'codigo_producto' => '74648119', 'codigo' => null, 'descripcion' => 'SILLA FIJA DE MADERA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => '2017', 'ubicacion' => 'ADMISION'],
            ['orden' => 7, 'codigo_producto' => '74644932', 'codigo' => null, 'descripcion' => 'MESA DE MADERA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => null, 'ubicacion' => 'ADMISION'],
            ['orden' => 8, 'codigo_producto' => '74644932', 'codigo' => null, 'descripcion' => 'MESA DE MADERA', 'marca' => 'SIN MARCA', 'modelo' => 'SIN MODELO', 'serie' => 'SIN SERIE', 'costo' => 0.00, 'condicion' => 'B', 'tipo_adquisicion' => 'C', 'anio_adquisicion' => null, 'ubicacion' => 'ADMISION'],
        ];

        foreach ($admisionAssets as $item) {
            $item['area_id'] = $areaAdmision->id;
            $item['user_id'] = $admin?->id;
            Asset::firstOrCreate(
                ['area_id' => $areaAdmision->id, 'orden' => $item['orden']],
                $item
            );
        }
    }
}
