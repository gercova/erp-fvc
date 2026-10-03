<?php

namespace Database\Seeders;

use App\Enums\ServiceCategory;
use App\Models\Area;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductiveActivity;
use App\Models\TechnologicalService;
use App\Services\Agreements\AgreementCodeService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TechnologicalServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Ensure Service Category in POS/Product categories
        $category = Category::firstOrCreate(
            ['descripcion' => 'SERVICIOS TECNOLOGICOS']
        );

        // 2. Ensure Unit of Service (ZZ - Servicio Sunat)
        $unitId = DB::table('units')->where('codigo', 'ZZ')->value('id');
        if (!$unitId) {
            $unitId = DB::table('units')->insertGetId([
                'codigo'      => 'ZZ',
                'descripcion' => 'SERVICIO',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        // 3. Ensure IGV affection (20 - Exonerado or 10 - Gravado)
        $igvId = DB::table('igv_type_affections')->where('codigo', '20')->value('id')
            ?: DB::table('igv_type_affections')->orderBy('id')->value('id');

        // 4. Ensure Area for services
        $area = Area::where('code', 'PROD')->first()
            ?: Area::firstOrCreate(
                ['code' => 'PROD'],
                ['name' => 'Área de Producción y Servicios', 'type' => 'area', 'level' => 2]
            );

        // 5. Ensure Productive Activity (Cost Center)
        $activity = ProductiveActivity::where('code', 'ACT-STEC')->first()
            ?: ProductiveActivity::firstOrCreate(
                ['code' => 'ACT-STEC'],
                [
                    'uuid'             => (string) Str::uuid(),
                    'name'             => 'Centro de Costos - Servicios Tecnológicos',
                    'type'             => 'SERVICES',
                    'cost_center_code' => 'CC-STEC-01',
                    'area_id'          => $area->id,
                    'status'           => 'ACTIVA',
                    'order_index'      => 10,
                ]
            );

        // 6. Base Technological Services Definition
        $servicesCatalog = [
            [
                'service_code'         => 'SERV-2026-0001',
                'product_internal'     => 'STEC-0001',
                'name'                 => 'Análisis de Fertilidad y Caracterización de Suelos Agrícolas',
                'description'          => 'Determinación de pH, materia orgánica, fósforo disponible, potasio y textura en laboratorio de suelos.',
                'category'             => ServiceCategory::LABORATORY_ANALYSIS,
                'delivery_modality'    => 'IN_PERSON',
                'unit_price'           => 150.00,
                'estimated_hours'      => 8,
                'requires_deliverable' => true,
            ],
            [
                'service_code'         => 'SERV-2026-0002',
                'product_internal'     => 'STEC-0002',
                'name'                 => 'Catación y Análisis Físico-Sensorial de Café y Cacao',
                'description'          => 'Evaluación de perfil organoléptico, humedad, defectos y puntaje en taza según protocolo SCA.',
                'category'             => ServiceCategory::LABORATORY_ANALYSIS,
                'delivery_modality'    => 'IN_PERSON',
                'unit_price'           => 85.00,
                'estimated_hours'      => 4,
                'requires_deliverable' => true,
            ],
            [
                'service_code'         => 'SERV-2026-0003',
                'product_internal'     => 'STEC-0003',
                'name'                 => 'Asistencia Técnica Especializada en Manejo Integrado de Plagas (MIP)',
                'description'          => 'Inspección fitosanitaria de parcelas, monitoreo de plagas/enfermedades y emisión de plan de tratamiento agroecológico.',
                'category'             => ServiceCategory::TECHNICAL_ASSISTANCE,
                'delivery_modality'    => 'FIELD',
                'unit_price'           => 350.00,
                'estimated_hours'      => 16,
                'requires_deliverable' => true,
            ],
            [
                'service_code'         => 'SERV-2026-0004',
                'product_internal'     => 'STEC-0004',
                'name'                 => 'Curso - Taller Práctico de Injerto y Poda en Cacao Fino de Aroma',
                'description'          => 'Capacitación presencial con entrega de manual, materiales y certificación técnica institucional.',
                'category'             => ServiceCategory::TRAINING,
                'delivery_modality'    => 'HYBRID',
                'unit_price'           => 120.00,
                'estimated_hours'      => 20,
                'requires_deliverable' => true,
            ],
            [
                'service_code'         => 'SERV-2026-0005',
                'product_internal'     => 'STEC-0005',
                'name'                 => 'Consultoría y Formulación de Planes de Negocio Agrario (Agroideas / Procompite)',
                'description'          => 'Formulación técnica, económica y financiera de planes de negocio para organizaciones de productores.',
                'category'             => ServiceCategory::CONSULTING,
                'delivery_modality'    => 'HYBRID',
                'unit_price'           => 1800.00,
                'estimated_hours'      => 40,
                'requires_deliverable' => true,
            ],
            [
                'service_code'         => 'SERV-2026-0006',
                'product_internal'     => 'STEC-0006',
                'name'                 => 'Servicio de Maquinaria y Equipamiento Agroindustrial',
                'description'          => 'Alquiler con operador de tractor agrícola, trilladora y picadora de forraje para socios y terceros.',
                'category'             => ServiceCategory::OTHER,
                'delivery_modality'    => 'IN_PERSON',
                'unit_price'           => 250.00,
                'estimated_hours'      => 6,
                'requires_deliverable' => false,
            ],
        ];

        foreach ($servicesCatalog as $item) {
            // 1. Upsert Product (opcion = 2 for service, stock_actual = null, no stock movement)
            $product = Product::updateOrCreate(
                ['codigo_interno' => $item['product_internal']],
                [
                    'codigo_barras'  => $item['product_internal'],
                    'codigo_sunat'   => '78102203',
                    'descripcion'    => $item['name'],
                    'idunidad'       => $unitId,
                    'idcategoria'    => $category->id,
                    'igv'            => 0,
                    'idcodigo_igv'   => $igvId,
                    'precio_compra'  => 0.00,
                    'precio_venta'   => $item['unit_price'],
                    'opcion'         => 2, // 2 = SERVICE, never tracks inventory stock
                    'stock_actual'   => null,
                ]
            );

            // 2. Upsert TechnologicalService referencing the product
            TechnologicalService::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'code'                   => $item['service_code'],
                    'name'                   => $item['name'],
                    'description'            => $item['description'],
                    'area_id'                => $area->id,
                    'productive_activity_id' => $activity->id,
                    'category'               => $item['category'],
                    'delivery_modality'      => $item['delivery_modality'],
                    'unit_price'             => $item['unit_price'],
                    'currency'               => 'PEN',
                    'estimated_hours'        => $item['estimated_hours'],
                    'requires_deliverable'   => $item['requires_deliverable'],
                    'is_active'              => true,
                ]
            );
        }
    }
}
