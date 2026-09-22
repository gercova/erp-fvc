<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Cash;
use App\Models\EmployeeAreaDetail;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AreaAndUserManagementTest extends TestCase
{
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('user', 'admin')->first() ?? User::factory()->create([
            'user' => 'admin',
            'estado' => 1,
            'idcaja' => 1,
            'idalmacen' => 1,
        ]);

        if (! $this->adminUser->hasRole('SUPERADMIN')) {
            $role = Role::firstOrCreate(['name' => 'SUPERADMIN']);
            $this->adminUser->assignRole($role);
        }
    }

    public function test_areas_index_view_is_accessible()
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.areas'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.areas.list');
        $response->assertSee('Gestión de Áreas y Unidades');
    }

    public function test_can_create_update_and_delete_area()
    {
        $uniqueCode = 'TEST_AREA_' . time();

        // 1. Create Area
        $saveResponse = $this->actingAs($this->adminUser)->postJson(route('areas.save'), [
            'code' => $uniqueCode,
            'name' => 'Área de Prueba Automatizada',
            'type' => 'area',
            'level' => 2,
            'is_advisory' => 0,
        ]);

        $saveResponse->assertStatus(200);
        $saveResponse->assertJson(['status' => true]);

        $area = Area::where('code', $uniqueCode)->first();
        $this->assertNotNull($area);
        $this->assertEquals('Área de Prueba Automatizada', $area->name);

        // 2. Detail Area
        $detailResponse = $this->actingAs($this->adminUser)->postJson(route('areas.detail'), [
            'id' => $area->id,
        ]);
        $detailResponse->assertStatus(200);
        $detailResponse->assertJson(['status' => true]);
        $this->assertEquals($uniqueCode, $detailResponse->json('area.code'));

        // 3. Update Area
        $updateResponse = $this->actingAs($this->adminUser)->postJson(route('areas.store'), [
            'id' => $area->id,
            'code' => $uniqueCode,
            'name' => 'Área de Prueba Modificada',
            'type' => 'unidad',
            'level' => 3,
            'is_advisory' => 1,
        ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJson(['status' => true]);
        $area->refresh();
        $this->assertEquals('Área de Prueba Modificada', $area->name);
        $this->assertEquals('unidad', $area->type);
        $this->assertTrue((bool) $area->is_advisory);

        // 4. Delete Area
        $deleteResponse = $this->actingAs($this->adminUser)->postJson(route('areas.delete'), [
            'id' => $area->id,
        ]);
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['status' => true]);

        $this->assertSoftDeleted('areas', ['id' => $area->id]);
    }

    public function test_user_creation_with_digital_signature_and_area()
    {
        $cash = Cash::first();
        $warehouse = Warehouse::first();
        $area = Area::first();

        $userName = 'testuser_' . time();
        $signatureFile = UploadedFile::fake()->image('signature_sample.png', 200, 100);

        $response = $this->actingAs($this->adminUser)->post(route('users.save'), [
            'nombres' => 'JUAN PEREZ AUTOMATION',
            'user' => $userName,
            'password' => 'secret123',
            'role' => 'VENDEDOR',
            'estado' => 1,
            'idcaja' => $cash->id,
            'warehouse_ids' => [$warehouse->id],
            'area_id' => $area->id,
            'cargo' => 'Asistente Administrativo',
            'condicion_laboral' => 'NOMBRADO',
            'firma_digital' => $signatureFile,
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $user = User::where('user', $userName)->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->firma_digital);
        $this->assertFileExists(public_path($user->firma_digital));

        // Verify EmployeeAreaDetail
        $detail = EmployeeAreaDetail::where('user_id', $user->id)->first();
        $this->assertNotNull($detail);
        $this->assertEquals($area->id, $detail->area_id);
        $this->assertEquals('Asistente Administrativo', $detail->cargo);
        $this->assertEquals('NOMBRADO', $detail->condicion_laboral);
        $this->assertTrue((bool) $detail->is_primary);

        // Clean up created file
        if (File::exists(public_path($user->firma_digital))) {
            File::delete(public_path($user->firma_digital));
        }

        // Clean up test records
        $detail->delete();
        $user->warehouses()->detach();
        $user->delete();
    }

    public function test_user_template_download()
    {
        $response = $this->actingAs($this->adminUser)->get(route('users.download_template'));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_bulk_excel_import_creates_users_with_area_and_roles()
    {
        $area = Area::first();

        $rows = collect([
            [
                'nombres' => 'Bulk User Test Alpha',
                'usuario' => 'bulkuser_alpha',
                'correo' => 'alpha@test.com',
                'telefono' => '999888777',
                'contrasena' => 'bulk12345',
                'rol' => 'VENDEDOR',
                'caja' => '1',
                'almacen' => '1',
                'codigo_area' => $area->code,
                'cargo' => 'Asistente de Ventas',
                'condicion_laboral' => 'CAS',
                'estado' => '1',
            ],
            [
                'nombres' => 'Bulk User Test Beta',
                'usuario' => 'bulkuser_beta',
                'correo' => 'beta@test.com',
                'telefono' => '999888776',
                'contrasena' => 'bulk12345',
                'rol' => 'ADMIN',
                'caja' => '1',
                'almacen' => '1',
                'codigo_area' => $area->code,
                'cargo' => 'Jefe de Operaciones',
                'condicion_laboral' => 'NOMBRADO',
                'estado' => '1',
            ],
        ]);

        $import = new \App\Imports\UsersImport();
        $import->collection($rows);

        $summary = $import->getSummary();
        $this->assertEquals(2, $summary['created']);

        $userAlpha = User::where('user', 'bulkuser_alpha')->first();
        $this->assertNotNull($userAlpha);
        $this->assertEquals('BULK USER TEST ALPHA', $userAlpha->nombres);
        $this->assertTrue($userAlpha->hasRole('VENDEDOR'));

        $detailAlpha = EmployeeAreaDetail::where('user_id', $userAlpha->id)->first();
        $this->assertNotNull($detailAlpha);
        $this->assertEquals($area->id, $detailAlpha->area_id);
        $this->assertEquals('Asistente de Ventas', $detailAlpha->cargo);
        $this->assertEquals('CAS', $detailAlpha->condicion_laboral);

        $userBeta = User::where('user', 'bulkuser_beta')->first();
        $this->assertNotNull($userBeta);
        $this->assertTrue($userBeta->hasRole('ADMIN'));

        // Clean up
        $detailAlpha->delete();
        $userAlpha->warehouses()->detach();
        $userAlpha->delete();

        EmployeeAreaDetail::where('user_id', $userBeta->id)->delete();
        $userBeta->warehouses()->detach();
        $userBeta->delete();
    }

    public function test_cannot_delete_area_with_child_areas_or_employees()
    {
        $parentArea = Area::create([
            'code' => 'TEST_PARENT_' . time(),
            'name' => 'Área Padre Test',
            'type' => 'direccion_general',
            'level' => 1,
        ]);

        $childArea = Area::create([
            'code' => 'TEST_CHILD_' . time(),
            'name' => 'Área Hija Test',
            'type' => 'area',
            'parent_id' => $parentArea->id,
            'level' => 2,
        ]);

        // Attempt to delete parent should fail
        $deleteParent = $this->actingAs($this->adminUser)->postJson(route('areas.delete'), [
            'id' => $parentArea->id,
        ]);
        $deleteParent->assertStatus(422);
        $deleteParent->assertJson(['status' => false]);

        // Delete child area first
        $deleteChild = $this->actingAs($this->adminUser)->postJson(route('areas.delete'), [
            'id' => $childArea->id,
        ]);
        $deleteChild->assertStatus(200);
        $deleteChild->assertJson(['status' => true]);

        // Now parent area can be deleted
        $deleteParentRetry = $this->actingAs($this->adminUser)->postJson(route('areas.delete'), [
            'id' => $parentArea->id,
        ]);
        $deleteParentRetry->assertStatus(200);
        $deleteParentRetry->assertJson(['status' => true]);
    }
}
