<?php

namespace Database\Seeders;

use App\Models\IgvTypeAffection;
use Illuminate\Database\Seeder;

class IgvTypeAffectionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['codigo' => '10', 'descripcion' => 'Gravado - Operacion Onerosa', 'tipo' => 'GRAV', 'estado' => true],
            ['codigo' => '11', 'descripcion' => 'Gravado - Retiro por premio', 'tipo' => 'GRAV', 'estado' => true],
            ['codigo' => '15', 'descripcion' => 'Gravado - Bonificaciones', 'tipo' => 'GRAV', 'estado' => true],
            ['codigo' => '20', 'descripcion' => 'Exonerado - Operacion Onerosa', 'tipo' => 'EXO', 'estado' => true],
            ['codigo' => '21', 'descripcion' => 'Exonerado - Transferencia Gratuita', 'tipo' => 'EXO', 'estado' => true],
            ['codigo' => '30', 'descripcion' => 'Inafecto - Operacion Onerosa', 'tipo' => 'INA', 'estado' => true],
            ['codigo' => '31', 'descripcion' => 'Inafecto - Transferencia Gratuita', 'tipo' => 'INA', 'estado' => true],
            ['codigo' => '40', 'descripcion' => 'Exportacion', 'tipo' => 'EXP', 'estado' => true],
        ];

        foreach ($rows as $row) {
            IgvTypeAffection::updateOrCreate(['codigo' => $row['codigo']], $row);
        }
    }
}
