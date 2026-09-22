<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetInventory;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    protected User $adminUser;
    protected Area $testArea;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('user', 'admin')->first() ?? User::factory()->create([
            'user' => 'admin',
            'estado' => 1,
            'idcaja' => 1,
            'idalmacen' => 1,
        ]);

        if (!$this->adminUser->hasRole('SUPERADMIN')) {
            $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
            $this->adminUser->assignRole($role);
        }

        $this->testArea = Area::first() ?? Area::create([
            'code' => 'TEST_AREA',
            'name' => 'Área de Prueba Test',
            'type' => 'area',
            'level' => 2,
        ]);
    }

    public function test_assets_index_view_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('inventory.index', ['area_id' => $this->testArea->id]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.assets.index');
        $response->assertSee('Bienes Patrimoniales');
        $response->assertSee('Formato Único Institucional');
    }

    public function test_can_create_asset_with_smart_defaults(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('inventory.store'), [
            'area_id'          => $this->testArea->id,
            'descripcion'      => 'SILLA FIJA DE MADERA TEST',
            'condicion'        => 'B',
            'tipo_adquisicion' => 'C',
            'ubicacion'        => 'OFICINA PRINCIPAL',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $asset = Asset::where('descripcion', 'SILLA FIJA DE MADERA TEST')->first();
        $this->assertNotNull($asset);
        $this->assertEquals('SIN MARCA', $asset->marca);
        $this->assertEquals('SIN MODELO', $asset->modelo);
        $this->assertEquals('SIN SERIE', $asset->serie);
        $this->assertEquals('0.00', $asset->costo);
        $this->assertEquals('B', $asset->condicion);
        $this->assertEquals('C', $asset->tipo_adquisicion);
        $this->assertNotEmpty($asset->uuid);
        $this->assertGreaterThanOrEqual(1, $asset->orden);
    }

    public function test_asset_qr_generation_and_public_verification(): void
    {
        $asset = Asset::create([
            'area_id'          => $this->testArea->id,
            'descripcion'      => 'LAPTOP CORE I7 TEST',
            'codigo'           => '07-22',
            'condicion'        => 'B',
            'tipo_adquisicion' => 'C',
            'ubicacion'        => 'LABORATORIO',
        ]);

        // 1. Verificar generación de SVG del QR
        $qrResponse = $this->get(route('inventory.qr_svg', $asset->uuid));
        $qrResponse->assertStatus(200);
        $qrResponse->assertHeader('Content-Type', 'image/svg+xml');

        // 2. Verificar ficha pública por escaneo móvil de QR
        $verifyResponse = $this->get(route('inventory.public_verify', $asset->uuid));
        $verifyResponse->assertStatus(200);
        $verifyResponse->assertSee('LAPTOP CORE I7 TEST');
        $verifyResponse->assertSee('07-22');
        $verifyResponse->assertSee('ACTIVO PATRIMONIAL OFICIAL');
    }

    public function test_asset_reconcile_toggle(): void
    {
        $asset = Asset::create([
            'area_id'          => $this->testArea->id,
            'descripcion'      => 'PROYECTOR MULTIMEDIA TEST',
            'condicion'        => 'B',
            'tipo_adquisicion' => 'C',
            'ubicacion'        => 'SALA DE ACTOS',
        ]);

        $this->assertFalse($asset->is_reconciled);

        // Toggle a verificado
        $response = $this->actingAs($this->adminUser)->postJson(route('inventory.reconcile', $asset->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_reconciled' => true]);

        $asset->refresh();
        $this->assertTrue($asset->is_reconciled);
        $this->assertNotNull($asset->reconciled_at);
        $this->assertEquals($this->adminUser->id, $asset->reconciled_by);
    }

    public function test_pdf_export_matches_institutional_layout(): void
    {
        Asset::create([
            'area_id'          => $this->testArea->id,
            'descripcion'      => 'ESCRITORIO DE METAL TEST',
            'codigo_producto'  => '74648119',
            'codigo'           => '088-12 01',
            'condicion'        => 'B',
            'tipo_adquisicion' => 'C',
            'costo'            => 65.00,
            'ubicacion'        => 'TESORERÍA',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('inventory.pdf', ['area_id' => $this->testArea->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_create_asset_inventory_with_approval_workflow(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson(route('asset-inventories.store'), [
            'area_id'          => $this->testArea->id,
            'titulo'           => 'INVENTARIO GENERAL DE PRUEBA 2026',
            'periodo'          => '2026',
            'fecha_inventario' => '2026-01-01',
            'responsable'      => 'RESPONSABLE PRUEBA',
            'realizado_por'    => 'Tec. OSORIO SANCHEZ, CECILIA ISABEL',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $inventory = AssetInventory::where('area_id', $this->testArea->id)
            ->where('periodo', '2026')
            ->first();

        $this->assertNotNull($inventory);
        $this->assertEquals('EN_REVISION', $inventory->status);

        // Verificar los 5 roles de firma requeridos (Screenshots 2 & 3)
        $approvals = $inventory->approvals;
        $this->assertCount(5, $approvals);

        $rolesInWorkflow = $approvals->pluck('role_name')->toArray();
        $this->assertContains('JEFE_AREA', $rolesInWorkflow);
        $this->assertContains('ABASTECIMIENTO', $rolesInWorkflow);
        $this->assertContains('ADMINISTRACION', $rolesInWorkflow);
        $this->assertContains('UNIDAD_ADMINISTRATIVA', $rolesInWorkflow);
        $this->assertContains('DIRECTOR_GENERAL', $rolesInWorkflow);
    }

    public function test_assets_streamlined_datatables_feed(): void
    {
        Asset::create([
            'area_id'          => $this->testArea->id,
            'descripcion'      => 'SILLA GIRATORIA TEST',
            'codigo_producto'  => '74648119',
            'codigo'           => '055-22',
            'marca'            => 'ERGOMASTER',
            'modelo'           => 'V1',
            'serie'            => 'SN-999',
            'condicion'        => 'B',
            'tipo_adquisicion' => 'C',
            'ubicacion'        => 'SALA DE COMPUTO',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('inventory.get', ['area_id' => $this->testArea->id]));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertNotEmpty($data['data']);

        $firstRow = $data['data'][0];
        // Verificar las 8 columnas esenciales
        $this->assertArrayHasKey('orden_fmt', $firstRow);
        $this->assertArrayHasKey('codigo_col', $firstRow);
        $this->assertArrayHasKey('descripcion_col', $firstRow);
        $this->assertArrayHasKey('tecnico_col', $firstRow);
        $this->assertArrayHasKey('condicion_badge', $firstRow);
        $this->assertArrayHasKey('ubicacion_col', $firstRow);
        $this->assertArrayHasKey('reconciled_badge', $firstRow);
        $this->assertArrayHasKey('acciones', $firstRow);
    }

    public function test_download_asset_template(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('inventory.download_template', ['area_id' => $this->testArea->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition');
    }

    public function test_quick_detail_endpoint(): void
    {
        $asset = Asset::create([
            'area_id'          => $this->testArea->id,
            'descripcion'      => 'MONITOR LED 24 TEST',
            'codigo'           => '099-15',
            'condicion'        => 'B',
            'tipo_adquisicion' => 'C',
            'ubicacion'        => 'LABORATORIO 1',
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('inventory.quick_detail', $asset->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'asset' => [
                'id'          => $asset->id,
                'codigo'      => '099-15',
                'descripcion' => 'MONITOR LED 24 TEST',
            ],
        ]);
    }

    public function test_assets_excel_import_with_institutional_format(): void
    {
        $area = Area::create([
            'code'  => 'IMP_' . time(),
            'name'  => 'Área de Importación de Prueba',
            'type'  => 'area',
            'level' => 2,
        ]);

        // Estructura idéntica al Excel del Screenshot oficial
        $rows = collect([
            // Filas 1 a 8: Encabezados y títulos institucionales
            ['INSTITUTO DE EDUCACIÓN SUPERIOR TECNOLÓGICO PÚBLICO "FRANCISCO VIGO CABALLERO"'],
            ['"Formando líderes en el Alto Huallaga"'],
            [''],
            ['INVENTARIO GENERAL DEL ÁREA DE ADMISION - CEPREISU2026'],
            ['RESPONSABLE: FELIX FRANCISCO BETSAIDA'],
            ['REALIZADO POR : Tec. OSORIO SANCHEZ,CECILIA ISABEL'],
            ['REALIZADO EL INVENTARIO EL 01 DE ENERO DEL 2026'],
            [''],
            // Fila 9: Encabezados de columnas
            ['Nº ORD', 'CODIGO PRODUCTO', 'CODIGO', 'DESCRIPCIÓN', 'MARCA', 'MODELO', 'SERIE', 'COSTO', 'CONDICIÓN', '', '', '', 'TIPO ADQ.', '', 'AÑO ADQ.', 'UBICACIÓN', 'OBSERVACIÓN'],
            // Fila 10: Subencabezados
            ['', '', '', '', '', '', '', '', 'B', 'R', 'M', 'BAJA', 'C', 'D', '', '', ''],
            // Fila 11+: Registros de datos (Screenshot)
            ['01', '', '0 7 - 2 2', 'LAPTOP IMPORT TEST', 'CORE 6', '', 'SIN SERIE', '', 'X', '', '', '', 'X', '', '2022', 'LABORATORIO', 'EN FUNCIONAMIENTO'],
            ['02', '74222358', '011 -21', 'IMPRESORA IMPORT TEST', '', '', 'SIN SERIE', '', 'x', '', '', '', 'x', '', '2021', 'OFICINA', ''],
            ['03', '74644932', '00 5 -17', 'MESA DE MADERA IMPORT TEST', '', '', 'SIN SERIE', '', 'X', '', '', '', 'X', '', '2017', 'AULA 1', ''],
            ['04', '74648119', '047 -11', 'SILLA FIJA DE MADERA IMPORT TEST', '', '', 'SIN SERIE', '', 'x', '', '', '', 'x', '', '2011', 'AULA 2', ''],
        ]);

        // 1. Primera pasada: debe crear los 4 activos
        $import = new \App\Imports\AssetsImport($area->id, $this->adminUser->id, true);
        $import->collection($rows);

        $summary = $import->getSummary();
        $this->assertEquals(4, $summary['created']);
        $this->assertEquals(0, $summary['updated']);

        // 2. Segunda pasada con overwrite: debe actualizar los 4 activos
        $reImport = new \App\Imports\AssetsImport($area->id, $this->adminUser->id, true);
        $reImport->collection($rows);
        $reSummary = $reImport->getSummary();
        $this->assertEquals(4, $reSummary['updated']);
        $this->assertEquals(0, $reSummary['created']);

        $laptop = Asset::where('area_id', $area->id)->where('descripcion', 'LAPTOP IMPORT TEST')->first();
        $this->assertNotNull($laptop);
        $this->assertEquals($area->id, $laptop->area_id);
        $this->assertEquals('0 7 - 2 2', $laptop->codigo);
        $this->assertEquals('CORE 6', $laptop->marca);
        $this->assertEquals('SIN MODELO', $laptop->modelo);
        $this->assertEquals('SIN SERIE', $laptop->serie);
        $this->assertEquals('B', $laptop->condicion);
        $this->assertEquals('C', $laptop->tipo_adquisicion);
        $this->assertEquals(2022, $laptop->anio_adquisicion);
        $this->assertEquals('LABORATORIO', $laptop->ubicacion);
        $this->assertEquals('EN FUNCIONAMIENTO', $laptop->observaciones);

        $silla = Asset::where('area_id', $area->id)->where('descripcion', 'SILLA FIJA DE MADERA IMPORT TEST')->first();
        $this->assertNotNull($silla);
        $this->assertEquals('74648119', $silla->codigo_producto);
        $this->assertEquals('047 -11', $silla->codigo);
        $this->assertEquals('SIN MARCA', $silla->marca);
        $this->assertEquals('SIN MODELO', $silla->modelo);
        $this->assertEquals('SIN SERIE', $silla->serie);
        $this->assertEquals('B', $silla->condicion);
        $this->assertEquals('C', $silla->tipo_adquisicion);
        $this->assertEquals(2011, $silla->anio_adquisicion);
    }

    public function test_upload_excel_http_endpoint(): void
    {
        $area = Area::create([
            'code'  => 'HTTP_' . time(),
            'name'  => 'Área HTTP Upload Test',
            'type'  => 'area',
            'level' => 2,
        ]);

        // Generar archivo Excel real con AssetTemplateExport
        $excelContent = \Maatwebsite\Excel\Facades\Excel::raw(
            new \App\Exports\AssetTemplateExport($area->name),
            \Maatwebsite\Excel\Excel::XLSX
        );

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('inventario_test.xlsx', $excelContent);

        $response = $this->actingAs($this->adminUser)->post(route('inventory.upload_excel'), [
            'area_id'   => $area->id,
            'overwrite' => '1',
            'excel'     => $file,
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
        ]);

        // Verificar que los activos de la plantilla se registraron en la base de datos
        $assets = Asset::where('area_id', $area->id)->get();
        $this->assertGreaterThanOrEqual(1, $assets->count());
    }
}
