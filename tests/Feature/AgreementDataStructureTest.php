<?php

namespace Tests\Feature;

use App\Enums\AddendumType;
use App\Enums\AgreementDocumentType;
use App\Enums\AgreementStatus;
use App\Enums\AgreementType;
use App\Enums\InstallmentStatus;
use App\Enums\ObligationStatus;
use App\Enums\ServiceCategory;
use App\Enums\ServiceDeliverableStatus;
use App\Enums\ServiceEngagementStatus;
use App\Enums\ServiceSessionStatus;
use App\Models\Agreement;
use App\Models\AgreementAddendum;
use App\Models\AgreementDocument;
use App\Models\AgreementInstallment;
use App\Models\AgreementObligation;
use App\Models\Area;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductiveActivity;
use App\Models\ServiceAttendee;
use App\Models\ServiceDeliverable;
use App\Models\ServiceEngagement;
use App\Models\ServiceSession;
use App\Models\StockProduct;
use App\Models\TechnologicalService;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Agreements\AgreementCodeService;
use App\Services\Agreements\AgreementFileService;
use App\Services\StockService;
use Database\Seeders\AgreementRoleAndPermissionSeeder;
use Database\Seeders\TechnologicalServiceCatalogSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgreementDataStructureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $coordinatorUser;
    protected User $unauthorizedUser;
    protected Client $client;
    protected Area $area;
    protected ProductiveActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Roles and Permissions
        $this->seed(AgreementRoleAndPermissionSeeder::class);

        $cashId = (int) (DB::table('cashes')->value('id') ?? 1);
        $warehouseId = (int) (DB::table('warehouses')->value('id') ?? 1);

        // 2. Base Users
        $this->adminUser = User::firstOrCreate(
            ['user' => 'admin_agreement_test'],
            [
                'nombres'   => 'Admin Agreement Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => $cashId,
                'idalmacen' => $warehouseId,
            ]
        );
        $this->adminUser->assignRole('SUPERADMIN');

        $this->coordinatorUser = User::firstOrCreate(
            ['user' => 'coord_agreement_test'],
            [
                'nombres'   => 'Coordinator Agreement Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => $cashId,
                'idalmacen' => $warehouseId,
            ]
        );
        $this->coordinatorUser->assignRole('COORDINADOR');

        $this->unauthorizedUser = User::firstOrCreate(
            ['user' => 'unauth_agreement_test'],
            [
                'nombres'   => 'Unauthorized User Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => $cashId,
                'idalmacen' => $warehouseId,
            ]
        );
        // Ensure no roles or permissions for unauthorized user
        $this->unauthorizedUser->syncRoles([]);
        $this->unauthorizedUser->syncPermissions([]);

        $docTypeId = (int) (DB::table('identity_document_types')->where('codigo', '6')->value('id') ?? 1);

        // 3. Base Entities
        $this->client = Client::firstOrCreate(
            ['nro_documento' => '20123456789'],
            [
                'iddoc'         => $docTypeId,
                'nombres'       => 'Empresa Agraria del Centro S.A.C.',
                'direccion'     => 'Av. Central 123, Satipo',
                'telefono'      => '987654321',
                'email'         => 'contacto@empresaagraria.com',
                'codigo_pais'   => 'PE',
                'ubigeo'        => '120601',
            ]
        );

        $this->area = Area::firstOrCreate(
            ['code' => 'PROD_TEST'],
            [
                'name'  => 'Área de Producción y Desarrollo Tecnológico',
                'type'  => 'area',
                'level' => 2,
            ]
        );

        $this->activity = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT_CONV_TEST'],
            [
                'uuid'             => (string) Str::uuid(),
                'name'             => 'Centro de Costos de Convenios y Transferencia',
                'type'             => 'SERVICES',
                'cost_center_code' => 'CC-CONV-01',
                'area_id'          => $this->area->id,
                'status'           => 'ACTIVA',
                'order_index'      => 99,
            ]
        );
    }

    /**
     * Test all 10 models and their bidirectional relationships and enums.
     */
    public function test_models_and_relationships_are_correctly_defined(): void
    {
        // 1. Framework Agreement
        $frameworkAgreement = Agreement::create([
            'type'                         => AgreementType::FRAMEWORK,
            'title'                        => 'Convenio Marco de Cooperación Interinstitucional',
            'objective'                    => 'Establecer bases para desarrollo tecnológico y capacitación agropecuaria',
            'client_id'                    => $this->client->id,
            'counterparty_signatory_name'  => 'Ing. Carlos Mendoza',
            'counterparty_signatory_role'  => 'Gerente General',
            'area_id'                      => $this->area->id,
            'coordinator_user_id'          => $this->coordinatorUser->id,
            'productive_activity_id'       => $this->activity->id,
            'signature_date'               => '2026-01-15',
            'start_date'                   => '2026-02-01',
            'end_date'                     => '2027-01-31',
            'currency'                     => 'PEN',
            'total_amount'                 => 100000.00,
            'counterparty_contribution'    => 60000.00,
            'institution_contribution'     => 40000.00,
            'status'                       => AgreementStatus::ACTIVE,
            'requires_financial_settlement'=> true,
            'created_by_user_id'           => $this->adminUser->id,
        ]);

        $this->assertNotNull($frameworkAgreement->id);
        $this->assertNotNull($frameworkAgreement->uuid);
        $this->assertStringStartsWith('CONV-2026-', $frameworkAgreement->code);
        $this->assertTrue($frameworkAgreement->isFramework());
        $this->assertTrue($frameworkAgreement->isActive());
        $this->assertEquals(AgreementType::FRAMEWORK, $frameworkAgreement->type);
        $this->assertEquals(AgreementStatus::ACTIVE, $frameworkAgreement->status);

        // 2. Specific Agreement linked to Framework
        $specificAgreement = Agreement::create([
            'type'                         => AgreementType::SPECIFIC,
            'parent_agreement_id'          => $frameworkAgreement->id,
            'title'                        => 'Convenio Específico N° 1 - Asistencia en Manejo de Cacao',
            'objective'                    => 'Capacitación y análisis de fertilidad de parcelas demostrativas',
            'client_id'                    => $this->client->id,
            'area_id'                      => $this->area->id,
            'coordinator_user_id'          => $this->coordinatorUser->id,
            'productive_activity_id'       => $this->activity->id,
            'signature_date'               => '2026-02-10',
            'start_date'                   => '2026-03-01',
            'end_date'                     => '2026-08-31',
            'currency'                     => 'PEN',
            'total_amount'                 => 25000.00,
            'status'                       => AgreementStatus::ACTIVE,
            'created_by_user_id'           => $this->adminUser->id,
        ]);

        $this->assertTrue($specificAgreement->isSpecific());
        $this->assertEquals($frameworkAgreement->id, $specificAgreement->parentAgreement->id);
        $this->assertTrue($frameworkAgreement->specificAgreements->contains($specificAgreement));

        // 3. Agreement Addendum
        $addendum = AgreementAddendum::create([
            'agreement_id'         => $specificAgreement->id,
            'addendum_type'        => AddendumType::TIME_EXTENSION,
            'title'                => 'Adenda N° 1 - Prórroga de Plazo por Lluvias',
            'justification'        => 'Ampliación de 60 días calendario debido a temporada de fuertes precipitaciones',
            'signature_date'       => '2026-08-15',
            'extended_end_date'    => '2026-10-31',
            'amount_modification'  => 0.00,
            'created_by_user_id'   => $this->adminUser->id,
        ]);

        $this->assertNotNull($addendum->id);
        $this->assertStringStartsWith('ADD-CONV-2026-', $addendum->code);
        $this->assertEquals(1, $addendum->addendum_number);
        $this->assertEquals($specificAgreement->id, $addendum->agreement->id);
        $this->assertTrue($specificAgreement->addenda->contains($addendum));

        // 4. Agreement Obligations
        $obligationInstitution = AgreementObligation::create([
            'agreement_id'      => $specificAgreement->id,
            'party'             => 'INSTITUTION',
            'description'       => 'Realizar 50 análisis de suelo y 4 talleres de poda',
            'due_date'          => '2026-07-31',
            'status'            => ObligationStatus::IN_PROGRESS,
            'responsible_party' => 'Área de Producción',
        ]);

        $obligationCounterparty = AgreementObligation::create([
            'agreement_id'      => $specificAgreement->id,
            'party'             => 'COUNTERPARTY',
            'description'       => 'Facilitar acceso a parcelas y abonar cronograma financiero',
            'due_date'          => '2026-05-15',
            'status'            => ObligationStatus::COMPLETED,
        ]);

        $this->assertCount(2, $specificAgreement->obligations);
        $this->assertEquals($specificAgreement->id, $obligationInstitution->agreement->id);

        // 5. Agreement Installments (Billing Schedule)
        $installment1 = AgreementInstallment::create([
            'agreement_id'       => $specificAgreement->id,
            'installment_number' => 1,
            'scheduled_date'     => '2026-03-15',
            'amount'             => 10000.00,
            'currency'           => 'PEN',
            'status'             => InstallmentStatus::PAID,
            'description'        => 'Primer desembolso al inicio de actividades',
        ]);

        $installment2 = AgreementInstallment::create([
            'agreement_id'       => $specificAgreement->id,
            'installment_number' => 2,
            'scheduled_date'     => '2026-06-15',
            'amount'             => 15000.00,
            'currency'           => 'PEN',
            'status'             => InstallmentStatus::SCHEDULED,
            'description'        => 'Segundo desembolso contra entrega de informes parciales',
        ]);

        $this->assertCount(2, $specificAgreement->installments);
        $this->assertEquals($specificAgreement->id, $installment1->agreement->id);

        // 6. Agreement Document
        $doc = AgreementDocument::create([
            'agreement_id'        => $specificAgreement->id,
            'document_type'       => AgreementDocumentType::SIGNED_AGREEMENT,
            'title'               => 'Convenio Específico Firmado y Foliado',
            'file_path'           => 'agreements/conv-test/documents/doc1.pdf',
            'file_size'           => 1024000,
            'mime_type'           => 'application/pdf',
            'uploaded_by_user_id' => $this->adminUser->id,
            'version'             => 1,
        ]);

        $this->assertEquals($specificAgreement->id, $doc->agreement->id);
        $this->assertTrue($specificAgreement->documents->contains($doc));

        // 7. Technological Service
        $product = Product::create([
            'codigo_interno' => 'SERV_PROD_001',
            'codigo_barras'  => 'SERV_PROD_001',
            'codigo_sunat'   => '78102203',
            'descripcion'    => 'Servicio de Análisis Químico de Suelos',
            'idunidad'       => 1,
            'idcategoria'    => 1,
            'igv'            => 0,
            'idcodigo_igv'   => 1,
            'precio_compra'  => 0.00,
            'precio_venta'   => 180.00,
            'opcion'         => 2, // SERVICE
            'stock_actual'   => null,
        ]);

        $techService = TechnologicalService::create([
            'product_id'             => $product->id,
            'name'                   => 'Análisis Químico Completo de Suelos',
            'description'            => 'Determinación de macro y micronutrientes',
            'area_id'                => $this->area->id,
            'productive_activity_id' => $this->activity->id,
            'category'               => ServiceCategory::LABORATORY_ANALYSIS,
            'delivery_modality'      => 'IN_PERSON',
            'unit_price'             => 180.00,
            'currency'               => 'PEN',
            'estimated_hours'        => 12,
            'requires_deliverable'   => true,
            'is_active'              => true,
        ]);

        $this->assertNotNull($techService->id);
        $this->assertStringStartsWith('SERV-2026-', $techService->code);
        $this->assertEquals($product->id, $techService->product->id);
        $this->assertEquals(ServiceCategory::LABORATORY_ANALYSIS, $techService->category);

        // 8. Service Engagement
        $engagement = ServiceEngagement::create([
            'agreement_id'             => $specificAgreement->id,
            'client_id'                => $this->client->id,
            'technological_service_id' => $techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->coordinatorUser->id,
            'description'              => 'Servicio de 20 análisis de suelo para parcelas piloto',
            'quantity'                 => 20.00,
            'unit_price'               => 180.00,
            'total_amount'             => 3600.00,
            'currency'                 => 'PEN',
            'start_date'               => '2026-03-05',
            'expected_delivery_date'   => '2026-04-15',
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
        ]);

        $this->assertNotNull($engagement->id);
        $this->assertStringStartsWith('ORD-2026-', $engagement->code);
        $this->assertTrue($engagement->isUnderAgreement());
        $this->assertEquals($specificAgreement->id, $engagement->agreement->id);
        $this->assertEquals($this->client->id, $engagement->client->id);
        $this->assertEquals($techService->id, $engagement->technologicalService->id);
        $this->assertEquals($this->coordinatorUser->id, $engagement->responsibleUser->id);

        // 9. Service Session
        $session = ServiceSession::create([
            'service_engagement_id' => $engagement->id,
            'topic'                 => 'Muestreo de suelo y toma de coordenadas geográficas',
            'instructor_user_id'    => $this->coordinatorUser->id,
            'session_date'          => '2026-03-10',
            'start_time'            => '08:00:00',
            'end_time'              => '12:00:00',
            'location'              => 'Parcela Demostrativa Sector Chanchamayo',
            'status'                => ServiceSessionStatus::CONDUCTED,
        ]);

        $this->assertEquals(1, $session->session_number);
        $this->assertEquals($engagement->id, $session->engagement->id);
        $this->assertEquals($this->coordinatorUser->id, $session->instructor->id);

        // 10. Service Attendee
        $attendee = ServiceAttendee::create([
            'service_session_id'    => $session->id,
            'service_engagement_id' => $engagement->id,
            'full_name'             => 'Juan Pérez Quispe',
            'dni_or_document'       => '45879632',
            'email'                 => 'juan.perez@gmail.com',
            'phone'                 => '963258741',
            'organization'          => 'Asociación de Productores de Cacao',
            'attended'              => true,
            'evaluation_score'      => 18.50,
            'certificate_code'      => 'CERT-2026-001',
        ]);

        $this->assertEquals($session->id, $attendee->session->id);
        $this->assertEquals($engagement->id, $attendee->engagement->id);
        $this->assertTrue($session->attendees->contains($attendee));

        // 11. Service Deliverable
        $deliverable = ServiceDeliverable::create([
            'service_engagement_id' => $engagement->id,
            'deliverable_name'      => 'Informe de Resultados de Análisis de Suelo',
            'description'           => 'Fichas técnicas y recomendaciones de fertilización',
            'due_date'              => '2026-04-15',
            'status'                => ServiceDeliverableStatus::PENDING,
        ]);

        $this->assertEquals($engagement->id, $deliverable->engagement->id);
        $this->assertTrue($engagement->deliverables->contains($deliverable));
    }

    /**
     * Test year-based sequential code generation with lockForUpdate.
     */
    public function test_sequential_code_generation_with_lock_for_update(): void
    {
        $codeService = app(AgreementCodeService::class);

        // 1. Agreement sequential codes
        $code1 = $codeService->generateAgreementCode(2026);
        $this->assertMatchesRegularExpression('/^CONV-2026-\d{4}$/', $code1);

        $ag1 = Agreement::create([
            'type'                   => AgreementType::FRAMEWORK,
            'title'                  => 'Convenio Prueba 1',
            'client_id'              => $this->client->id,
            'area_id'                => $this->area->id,
            'coordinator_user_id'    => $this->coordinatorUser->id,
            'productive_activity_id' => $this->activity->id,
            'start_date'             => '2026-01-01',
            'end_date'               => '2026-12-31',
            'currency'               => 'PEN',
            'total_amount'           => 1000.00,
        ]);

        $ag2 = Agreement::create([
            'type'                   => AgreementType::FRAMEWORK,
            'title'                  => 'Convenio Prueba 2',
            'client_id'              => $this->client->id,
            'area_id'                => $this->area->id,
            'coordinator_user_id'    => $this->coordinatorUser->id,
            'productive_activity_id' => $this->activity->id,
            'start_date'             => '2026-01-01',
            'end_date'               => '2026-12-31',
            'currency'               => 'PEN',
            'total_amount'           => 2000.00,
        ]);

        $this->assertNotEquals($ag1->code, $ag2->code);
        $num1 = (int) substr($ag1->code, -4);
        $num2 = (int) substr($ag2->code, -4);
        $this->assertEquals($num1 + 1, $num2);

        // 2. Addendum sequential codes
        $add1Code = $codeService->generateAddendumCode($ag1);
        $this->assertEquals("ADD-{$ag1->code}-01", $add1Code);

        $add1 = AgreementAddendum::create([
            'agreement_id'   => $ag1->id,
            'addendum_type'  => AddendumType::TIME_EXTENSION,
            'title'          => 'Adenda 1',
            'justification'  => 'Justificación 1',
            'signature_date' => '2026-06-01',
        ]);
        $this->assertEquals("ADD-{$ag1->code}-01", $add1->code);

        $add2 = AgreementAddendum::create([
            'agreement_id'   => $ag1->id,
            'addendum_type'  => AddendumType::AMOUNT_MODIFICATION,
            'title'          => 'Adenda 2',
            'justification'  => 'Justificación 2',
            'signature_date' => '2026-07-01',
        ]);
        $this->assertEquals("ADD-{$ag1->code}-02", $add2->code);

        // 3. Service Engagement sequential codes
        $eng1Code = $codeService->generateEngagementCode(2026);
        $this->assertMatchesRegularExpression('/^ORD-2026-\d{4}$/', $eng1Code);

        // 4. Technological Service sequential codes
        $serv1Code = $codeService->generateServiceCode(2026);
        $this->assertMatchesRegularExpression('/^SERV-2026-\d{4}$/', $serv1Code);
    }

    /**
     * Test TechnologicalService referencing Product (opcion=2) can be sold via POS/Billing without stock.
     */
    public function test_technological_service_can_be_sold_via_pos_without_requiring_or_generating_stock(): void
    {
        $warehouse = Warehouse::first() ?: Warehouse::create([
            'nombre'    => 'Almacén Principal Satipo',
            'direccion' => 'Satipo Centro',
            'estado'    => 1,
        ]);

        // Create a product of type service (opcion = 2)
        $serviceProduct = Product::create([
            'codigo_interno' => 'SRV_POS_TEST_99',
            'codigo_barras'  => 'SRV_POS_TEST_99',
            'codigo_sunat'   => '78102203',
            'descripcion'    => 'Servicio de Ensayo de Laboratorio para Convenio',
            'idunidad'       => 1,
            'idcategoria'    => 1,
            'igv'            => 0,
            'idcodigo_igv'   => 1,
            'precio_compra'  => 0.00,
            'precio_venta'   => 350.00,
            'opcion'         => 2, // 2 = Service
            'stock_actual'   => null,
        ]);

        // Link with TechnologicalService
        $techService = TechnologicalService::create([
            'product_id'             => $serviceProduct->id,
            'name'                   => 'Ensayo de Laboratorio para Convenio',
            'area_id'                => $this->area->id,
            'productive_activity_id' => $this->activity->id,
            'category'               => ServiceCategory::LABORATORY_ANALYSIS,
            'unit_price'             => 350.00,
            'currency'               => 'PEN',
            'estimated_hours'        => 6,
        ]);

        // Verify product model properties
        $this->assertTrue($serviceProduct->isService());
        $this->assertFalse($serviceProduct->isProduct());

        // Verify there is NO StockProduct row for this service product
        $initialStockCount = StockProduct::where('idproducto', $serviceProduct->id)->count();
        $this->assertEquals(0, $initialStockCount);

        // Attempting to decrease stock using StockService on a service product must return null and never fail
        $stockService = app(StockService::class);
        $result = $stockService->decrease($warehouse->id, $serviceProduct->id, 10);
        $this->assertNull($result);

        // Verify that still no StockProduct row exists
        $afterDecreaseCount = StockProduct::where('idproducto', $serviceProduct->id)->count();
        $this->assertEquals(0, $afterDecreaseCount);

        // Attempting to increase stock on a service product also returns null
        $increaseResult = $stockService->increase($warehouse->id, $serviceProduct->id, 10);
        $this->assertNull($increaseResult);
        $this->assertEquals(0, StockProduct::where('idproducto', $serviceProduct->id)->count());
    }

    /**
     * Test file storage in private storage and authorized download security.
     */
    public function test_private_file_storage_and_authorized_download(): void
    {
        Storage::fake('local');

        $agreement = Agreement::create([
            'type'                   => AgreementType::SPECIFIC,
            'title'                  => 'Convenio de Pruebas de Almacenamiento',
            'client_id'              => $this->client->id,
            'area_id'                => $this->area->id,
            'coordinator_user_id'    => $this->coordinatorUser->id,
            'productive_activity_id' => $this->activity->id,
            'start_date'             => '2026-01-01',
            'end_date'               => '2026-12-31',
            'currency'               => 'PEN',
            'total_amount'           => 50000.00,
        ]);

        $fileService = app(AgreementFileService::class);

        // 1. Upload Document via service
        $uploadedFile = UploadedFile::fake()->create('convenio_firmado.pdf', 500, 'application/pdf');

        $document = $fileService->storeDocument(
            $agreement,
            $uploadedFile,
            AgreementDocumentType::SIGNED_AGREEMENT,
            'Convenio Original Escaneado con Firmas',
            $this->coordinatorUser
        );

        $this->assertNotNull($document->id);
        $this->assertNotEmpty($document->file_path);
        Storage::disk('local')->assertExists($document->file_path);

        // 2. Unauthorized user gets 403 Forbidden
        $responseUnauth = $this->actingAs($this->unauthorizedUser)
            ->get(route('agreements.documents.download', ['document' => $document->id]));
        $responseUnauth->assertStatus(403);

        // 3. Agreement Coordinator can download (200 OK)
        $responseCoord = $this->actingAs($this->coordinatorUser)
            ->get(route('agreements.documents.download', ['document' => $document->id]));
        $responseCoord->assertStatus(200);

        // 4. Admin User can download (200 OK)
        $responseAdmin = $this->actingAs($this->adminUser)
            ->get(route('agreements.documents.download', ['document' => $document->id]));
        $responseAdmin->assertStatus(200);

        // 5. Deliverable evidence storage & download
        $product = Product::create([
            'codigo_interno' => 'SRV_DELIV_TEST',
            'codigo_barras'  => 'SRV_DELIV_TEST',
            'codigo_sunat'   => '78102203',
            'descripcion'    => 'Servicio con Entregable',
            'idunidad'       => 1,
            'idcategoria'    => 1,
            'igv'            => 0,
            'idcodigo_igv'   => 1,
            'precio_compra'  => 0.00,
            'precio_venta'   => 500.00,
            'opcion'         => 2,
            'stock_actual'   => null,
        ]);

        $techService = TechnologicalService::create([
            'product_id'             => $product->id,
            'name'                   => 'Servicio con Entregable',
            'area_id'                => $this->area->id,
            'productive_activity_id' => $this->activity->id,
            'category'               => ServiceCategory::CONSULTING,
            'unit_price'             => 500.00,
        ]);

        $engagement = ServiceEngagement::create([
            'agreement_id'             => $agreement->id,
            'client_id'                => $this->client->id,
            'technological_service_id' => $techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->coordinatorUser->id,
            'description'              => 'Estudio de Mercado y Plan de Capacitación',
            'unit_price'               => 500.00,
            'quantity'                 => 1.00,
            'total_amount'             => 500.00,
            'start_date'               => '2026-03-01',
            'expected_delivery_date'   => '2026-05-01',
        ]);

        $deliverable = ServiceDeliverable::create([
            'service_engagement_id' => $engagement->id,
            'deliverable_name'      => 'Informe Final de Estudio de Mercado',
            'description'           => 'Documento completo con estadísticas y análisis',
            'due_date'              => '2026-05-01',
            'status'                => ServiceDeliverableStatus::PENDING,
        ]);

        $delivFile = UploadedFile::fake()->create('informe_final.docx', 800, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $fileService->storeDeliverableFile($deliverable, $delivFile, $this->coordinatorUser);

        Storage::disk('local')->assertExists($deliverable->file_path);

        // Unauthorized download deliverable -> 403
        $delivUnauth = $this->actingAs($this->unauthorizedUser)
            ->get(route('agreements.deliverables.download', ['deliverable' => $deliverable->id]));
        $delivUnauth->assertStatus(403);

        // Coordinator download deliverable -> 200
        $delivCoord = $this->actingAs($this->coordinatorUser)
            ->get(route('agreements.deliverables.download', ['deliverable' => $deliverable->id]));
        $delivCoord->assertStatus(200);
    }

    /**
     * Test TechnologicalServiceCatalogSeeder execution and integrity.
     */
    public function test_technological_service_catalog_seeder_executes_cleanly_and_idempotently(): void
    {
        $this->seed(TechnologicalServiceCatalogSeeder::class);

        // Assert 6 services exist
        $services = TechnologicalService::with('product')->get();
        $this->assertGreaterThanOrEqual(6, $services->count());

        // Verify each service is linked to a product with opcion = 2 (Service)
        foreach ($services as $service) {
            $this->assertNotNull($service->product);
            $this->assertEquals(2, $service->product->opcion);
            $this->assertTrue($service->product->isService());
            $this->assertNull($service->product->stock_actual);
        }

        // Verify categories defined in enum are present
        $categoriesPresent = $services->pluck('category')->map(fn($c) => $c instanceof ServiceCategory ? $c->value : $c)->unique();
        $this->assertTrue($categoriesPresent->contains(ServiceCategory::LABORATORY_ANALYSIS->value));
        $this->assertTrue($categoriesPresent->contains(ServiceCategory::TECHNICAL_ASSISTANCE->value));
        $this->assertTrue($categoriesPresent->contains(ServiceCategory::TRAINING->value));
        $this->assertTrue($categoriesPresent->contains(ServiceCategory::CONSULTING->value));
        $this->assertTrue($categoriesPresent->contains(ServiceCategory::OTHER->value));

        // Running it again must be idempotent and succeed without duplicates
        $countBefore = TechnologicalService::count();
        $this->seed(TechnologicalServiceCatalogSeeder::class);
        $countAfter = TechnologicalService::count();
        $this->assertEquals($countBefore, $countAfter);
    }

    /**
     * Test uploading an agreement document via HTTP endpoint into private storage.
     */
    public function test_authorized_user_can_upload_agreement_document_to_private_storage(): void
    {
        Storage::fake('local');

        $agreement = Agreement::create([
            'type'                   => AgreementType::FRAMEWORK,
            'title'                  => 'Convenio para Carga de Documentos',
            'client_id'              => $this->client->id,
            'area_id'                => $this->area->id,
            'coordinator_user_id'    => $this->coordinatorUser->id,
            'productive_activity_id' => $this->activity->id,
            'start_date'             => '2026-01-01',
            'end_date'               => '2026-12-31',
            'currency'               => 'PEN',
            'total_amount'           => 10000.00,
        ]);

        $file = UploadedFile::fake()->create('resolucion_aprobacion.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->postJson(
            route('agreements.documents.upload', ['agreement' => $agreement->id]),
            [
                'document'      => $file,
                'document_type' => 'RESOLUTION',
                'title'         => 'Resolución de Consejo Directivo N° 045-2026',
            ]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Resolución de Consejo Directivo N° 045-2026');

        $documentId = $response->json('data.id');
        $document = AgreementDocument::find($documentId);
        $this->assertNotNull($document);
        $this->assertEquals(AgreementDocumentType::RESOLUTION, $document->document_type);
        Storage::disk('local')->assertExists($document->file_path);
    }

    /**
     * Test billing a technological service via SaleNote without stock impact.
     */
    public function test_technological_service_can_be_billed_via_sale_note_or_pos_detail_without_stock(): void
    {
        $warehouse = Warehouse::first() ?: Warehouse::create([
            'nombre'    => 'Almacén Principal Satipo',
            'direccion' => 'Satipo Centro',
            'estado'    => 1,
        ]);

        $serviceProduct = Product::create([
            'codigo_interno' => 'SRV_BILLED_01',
            'codigo_barras'  => 'SRV_BILLED_01',
            'codigo_sunat'   => '78102203',
            'descripcion'    => 'Servicio de Certificación y Catación Especial',
            'idunidad'       => 1,
            'idcategoria'    => 1,
            'igv'            => 0,
            'idcodigo_igv'   => 1,
            'precio_compra'  => 0.00,
            'precio_venta'   => 200.00,
            'opcion'         => 2, // 2 = Service
            'stock_actual'   => null,
        ]);

        $techService = TechnologicalService::create([
            'product_id'             => $serviceProduct->id,
            'name'                   => 'Certificación y Catación Especial',
            'area_id'                => $this->area->id,
            'productive_activity_id' => $this->activity->id,
            'category'               => ServiceCategory::LABORATORY_ANALYSIS,
            'unit_price'             => 200.00,
        ]);

        // Verify product is treated as service
        $this->assertTrue($techService->product->isService());

        // Simulate sale detail creation as POS does
        $saleNote = \App\Models\SaleNote::create([
            'idtipo_comprobante' => 1,
            'serie'              => 'NV01',
            'correlativo'        => '00000555',
            'fecha_emision'      => now()->toDateString(),
            'fecha_vencimiento'  => now()->toDateString(),
            'hora'               => '10:00:00',
            'idcliente'          => $this->client->id,
            'modo_pago'          => 1,
            'subtotal'           => 200.00,
            'igv'                => 0.00,
            'total'              => 200.00,
            'estado'             => 1,
            'idusuario'          => $this->adminUser->id,
            'idarqueocaja'       => 1,
        ]);

        $detail = \App\Models\DetailSaleNote::create([
            'idnotaventa'     => $saleNote->id,
            'idproducto'      => $serviceProduct->id,
            'cantidad'        => 1,
            'precio_unitario' => 200.00,
            'precio_total'    => 200.00,
            'descuento'       => 0,
            'igv'             => 0,
            'opcion'          => 2, // SERVICE
            'idalmacen'       => $warehouse->id,
        ]);

        $this->assertNotNull($detail->id);
        $this->assertEquals(2, $detail->opcion);

        // Crucial: StockProduct count remains 0, no physical inventory row created
        $stockCount = StockProduct::where('idproducto', $serviceProduct->id)->count();
        $this->assertEquals(0, $stockCount);
    }
}
