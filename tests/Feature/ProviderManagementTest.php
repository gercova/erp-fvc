<?php

namespace Tests\Feature;

use App\Models\Buy;
use App\Models\IdentityDocumentType;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProviderManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected IdentityDocumentType $rucDocType;
    protected IdentityDocumentType $dniDocType;

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

        $this->rucDocType = IdentityDocumentType::where('codigo', '6')->firstOrFail();
        $this->dniDocType = IdentityDocumentType::where('codigo', '1')->firstOrFail();
    }

    public function test_can_create_read_update_and_delete_provider_with_tax_details(): void
    {
        $uniqueRuc = '20' . str_pad((string) mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        // 1. Create provider
        $saveResponse = $this->actingAs($this->adminUser)->postJson(route('providers.save'), [
            'tipo_documento' => $this->rucDocType->id,
            'dni_ruc' => $uniqueRuc,
            'razon_social' => 'AGROINDUSTRIAS DEL NORTE SAC',
            'direccion' => 'AV. LOS LAURELES 450 URB. INDUSTRIAL',
            'departamento' => '15',
            'provincia' => '1501',
            'distrito' => '150101',
            'telefono' => '987654321',
            'email' => 'ventas@agronorte.com',
        ]);

        $saveResponse->assertStatus(200);
        $saveResponse->assertJson([
            'status' => true,
            'type' => 'success',
        ]);

        $providerId = $saveResponse->json('last_id');
        $this->assertNotNull($providerId);

        $this->assertDatabaseHas('providers', [
            'id' => $providerId,
            'iddoc' => $this->rucDocType->id,
            'nro_documento' => $uniqueRuc,
            'nombres' => 'AGROINDUSTRIAS DEL NORTE SAC',
            'direccion' => 'AV. LOS LAURELES 450 URB. INDUSTRIAL',
            'ubigeo' => '150101',
            'telefono' => '987654321',
            'email' => 'ventas@agronorte.com',
        ]);

        // 2. Read provider detail
        $detailResponse = $this->actingAs($this->adminUser)->postJson(route('providers.detail'), [
            'id' => $providerId,
        ]);
        $detailResponse->assertStatus(200);
        $detailResponse->assertJson([
            'status' => true,
            'provider' => [
                'id' => $providerId,
                'nro_documento' => $uniqueRuc,
                'nombres' => 'AGROINDUSTRIAS DEL NORTE SAC',
            ],
        ]);

        // 3. Update provider
        $updateResponse = $this->actingAs($this->adminUser)->postJson(route('providers.store'), [
            'id' => $providerId,
            'tipo_documento' => $this->rucDocType->id,
            'dni_ruc' => $uniqueRuc,
            'razon_social' => 'AGROINDUSTRIAS DEL NORTE SAC EDITADO',
            'direccion' => 'CALLE NUEVA 999',
            'departamento' => '15',
            'provincia' => '1501',
            'distrito' => '150101',
            'telefono' => '911222333',
            'email' => 'contacto@agronorte.com',
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('providers', [
            'id' => $providerId,
            'nombres' => 'AGROINDUSTRIAS DEL NORTE SAC EDITADO',
            'direccion' => 'CALLE NUEVA 999',
            'telefono' => '911222333',
            'email' => 'contacto@agronorte.com',
        ]);

        // 4. Delete provider
        $deleteResponse = $this->actingAs($this->adminUser)->postJson(route('providers.delete'), [
            'id' => $providerId,
        ]);
        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJson(['status' => true]);

        $this->assertDatabaseMissing('providers', [
            'id' => $providerId,
        ]);
    }

    public function test_uniqueness_constraint_prevents_duplicate_provider_by_document(): void
    {
        $duplicateRuc = '20' . str_pad((string) mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        // 1. Create first provider
        Provider::create([
            'iddoc' => $this->rucDocType->id,
            'nro_documento' => $duplicateRuc,
            'nombres' => 'PROVEEDOR ORIGINAL SAC',
            'direccion' => 'CALLE PRINCIPAL 100',
            'codigo_pais' => 'PE',
            'ubigeo' => '150101',
        ]);

        // 2. Attempt to register a duplicate provider with same document type and number
        $duplicateResponse = $this->actingAs($this->adminUser)->postJson(route('providers.save'), [
            'tipo_documento' => $this->rucDocType->id,
            'dni_ruc' => $duplicateRuc,
            'razon_social' => 'OTRO PROVEEDOR CON MISMO RUC',
            'direccion' => 'CALLE SECUNDARIA 200',
        ]);

        $duplicateResponse->assertStatus(422);
        $this->assertStringContainsString(
            'El proveedor con este tipo y número de documento ya se encuentra registrado.',
            $duplicateResponse->json('msg')
        );
    }

    public function test_provider_validation_document_length_and_mandatory_fields(): void
    {
        // 1. RUC with invalid length (10 digits instead of 11)
        $badRucResponse = $this->actingAs($this->adminUser)->postJson(route('providers.save'), [
            'tipo_documento' => $this->rucDocType->id,
            'dni_ruc' => '2012345678', // 10 digits
            'razon_social' => 'PROVEEDOR PRUEBA',
            'direccion' => 'AV. PRUEBA 123',
        ]);
        $badRucResponse->assertStatus(422);

        // 2. DNI with invalid length (7 digits instead of 8)
        $badDniResponse = $this->actingAs($this->adminUser)->postJson(route('providers.save'), [
            'tipo_documento' => $this->dniDocType->id,
            'dni_ruc' => '4785236', // 7 digits
            'razon_social' => 'JUAN PEREZ',
            'direccion' => 'JR. LIMA 100',
        ]);
        $badDniResponse->assertStatus(422);

        // 3. Missing mandatory fields
        $missingFieldsResponse = $this->actingAs($this->adminUser)->postJson(route('providers.save'), [
            'tipo_documento' => $this->rucDocType->id,
            'dni_ruc' => '',
            'razon_social' => '',
            'direccion' => '',
        ]);
        $missingFieldsResponse->assertStatus(422);
    }

    public function test_purchasing_module_load_providers_contains_tax_details(): void
    {
        $uniqueDoc = '20' . str_pad((string) mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);

        $provider = Provider::create([
            'iddoc' => $this->rucDocType->id,
            'nro_documento' => $uniqueDoc,
            'nombres' => 'DISTRIBUIDORA FISCAL SA',
            'direccion' => 'AV. TRIBUTARIA 789',
            'codigo_pais' => 'PE',
            'ubigeo' => '150101',
            'telefono' => '999111222',
            'email' => 'tributario@distribuidora.pe',
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('admin.load_providers'));

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $providers = collect($response->json('providers'));
        $found = $providers->firstWhere('id', $provider->id);

        $this->assertNotNull($found, 'Provider must be present in load_providers response');
        $this->assertEquals($uniqueDoc, $found['nro_documento']);
        $this->assertEquals('DISTRIBUIDORA FISCAL SA', $found['nombres']);
        $this->assertEquals('AV. TRIBUTARIA 789', $found['direccion']);
        $this->assertEquals('150101', $found['ubigeo']);
        $this->assertEquals('999111222', $found['telefono']);
        $this->assertEquals('tributario@distribuidora.pe', $found['email']);
        $this->assertNotEmpty($found['tipo_documento']);
    }

    public function test_provider_cannot_be_deleted_if_it_has_buys(): void
    {
        $provider = Provider::create([
            'iddoc' => $this->rucDocType->id,
            'nro_documento' => '20' . str_pad((string) mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            'nombres' => 'PROVEEDOR CON COMPRAS SAC',
            'direccion' => 'AV. COMERCIO 500',
            'codigo_pais' => 'PE',
        ]);

        // Create a buy linked to this provider
        $buy = Buy::create([
            'idtipo_comprobante' => 1,
            'serie' => 'F001',
            'correlativo' => 999999,
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->toDateString(),
            'hora' => now()->toTimeString(),
            'idproveedor' => $provider->id,
            'idmoneda' => 1,
            'idpago' => 1,
            'modo_pago' => 1,
            'exonerada' => 0.00,
            'inafecta' => 0.00,
            'gravada' => 84.75,
            'anticipo' => 0.00,
            'igv' => 15.25,
            'gratuita' => 0.00,
            'otros_cargos' => 0.00,
            'total' => 100.00,
            'estado' => 1,
            'idusuario' => $this->adminUser->id,
            'idalmacen' => 1,
        ]);

        $deleteResponse = $this->actingAs($this->adminUser)->postJson(route('providers.delete'), [
            'id' => $provider->id,
        ]);

        $deleteResponse->assertStatus(422);
        $deleteResponse->assertJson([
            'status' => false,
            'msg' => 'No se puede eliminar el proveedor porque tiene compras registradas en el sistema.',
        ]);

        $this->assertDatabaseHas('providers', [
            'id' => $provider->id,
        ]);
    }
}
