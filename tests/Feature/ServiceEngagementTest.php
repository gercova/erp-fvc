<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\AgreementType;
use App\Enums\ServiceCategory;
use App\Enums\ServiceDeliverableStatus;
use App\Enums\ServiceEngagementStatus;
use App\Models\Agreement;
use App\Models\Area;
use App\Models\Business;
use App\Models\Client;
use App\Models\ProductiveActivity;
use App\Models\ServiceAttendee;
use App\Models\ServiceDeliverable;
use App\Models\ServiceEngagement;
use App\Models\ServiceHourLog;
use App\Models\ServiceSession;
use App\Models\TechnologicalService;
use App\Models\User;
use Database\Seeders\AgreementRoleAndPermissionSeeder;
use Database\Seeders\TechnologicalServiceCatalogSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceEngagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $specialistUser;
    protected Client $client;
    protected Area $area;
    protected ProductiveActivity $activity;
    protected TechnologicalService $techService;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Roles & Permissions and Catalog
        $this->seed(AgreementRoleAndPermissionSeeder::class);
        $this->seed(TechnologicalServiceCatalogSeeder::class);

        $cashId = (int) (DB::table('cashes')->value('id') ?? 1);
        $warehouseId = (int) (DB::table('warehouses')->value('id') ?? 1);

        // 2. Ensure Business entity for certificates and reports
        $this->business = Business::firstOrCreate(
            ['ruc' => '20486523912'],
            [
                'razon_social'     => 'FUNDACION VALLE DEL CHANCHAMAYO',
                'nombre_comercial' => 'FVC - SERVICIOS TECNOLOGICOS',
                'direccion'        => 'Av. Principal 123, Chanchamayo, Junin',
                'telefono'         => '987654321',
                'idpais'           => 1,
                'codigo_pais'      => 'PE',
            ]
        );

        // 3. Admin User
        $this->adminUser = User::firstOrCreate(
            ['user' => 'admin_service_test'],
            [
                'nombres'   => 'Admin Service Tester',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => $cashId,
                'idalmacen' => $warehouseId,
            ]
        );
        $this->adminUser->assignRole('SUPERADMIN');

        // 4. Specialist User
        $this->specialistUser = User::firstOrCreate(
            ['user' => 'specialist_service_test'],
            [
                'nombres'   => 'Ing. Carlos Especialista',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => $cashId,
                'idalmacen' => $warehouseId,
            ]
        );
        $this->specialistUser->assignRole('COORDINADOR');

        // 5. Test Area & Productive Activity (Cost Center)
        $this->area = Area::firstOrCreate(
            ['code' => 'AREA_STEC_TEST'],
            [
                'name'  => 'Área de Servicios y Asistencia Técnica',
                'type'  => 'area',
                'level' => 2,
            ]
        );

        $this->activity = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT_STEC_TEST'],
            [
                'uuid'             => (string) Str::uuid(),
                'name'             => 'Centro de Costos de Asistencia Técnica',
                'type'             => 'SERVICES',
                'cost_center_code' => 'CC-STEC-TEST',
                'area_id'          => $this->area->id,
                'status'           => 'ACTIVA',
                'order_index'      => 10,
            ]
        );

        // 6. Test Client
        $docTypeId = (int) (DB::table('identity_document_types')->where('codigo', '6')->value('id') ?? 1);
        $this->client = Client::firstOrCreate(
            ['nro_documento' => '20789456123'],
            [
                'iddoc'         => $docTypeId,
                'nombres'       => 'Cooperativa Agraria Cafetalera Test',
                'direccion'     => 'Carretera Marginal Km 15, Satipo',
                'telefono'      => '963852741',
                'email'         => 'contacto@cooptest.pe',
                'codigo_pais'   => 'PE',
            ]
        );

        // 7. Technological Service
        $this->techService = TechnologicalService::where('category', ServiceCategory::TRAINING)->first()
            ?: TechnologicalService::first();

        if (!$this->techService) {
            $unitId = DB::table('units')->value('id') ?? 1;
            $catId  = DB::table('categories')->value('id') ?? 1;
            $igvId  = DB::table('igv_type_affections')->value('id') ?? 1;

            $product = \App\Models\Product::firstOrCreate(
                ['codigo_interno' => 'TEST-SRV-PROD-01'],
                [
                    'codigo_barras' => 'TEST-SRV-PROD-01',
                    'descripcion'   => 'Capacitación y Asistencia Técnica Test',
                    'idunidad'      => $unitId,
                    'idcategoria'   => $catId,
                    'idcodigo_igv'  => $igvId,
                    'precio_compra' => 0.00,
                    'precio_venta'  => 120.00,
                    'opcion'        => 2,
                    'stock_actual'  => null,
                ]
            );

            $this->techService = TechnologicalService::create([
                'code'                   => 'SRV-TEST-01',
                'product_id'             => $product->id,
                'name'                   => 'Capacitación y Asistencia Técnica en Cacao Fino',
                'category'               => ServiceCategory::TRAINING,
                'area_id'                => $this->area->id,
                'productive_activity_id' => $this->activity->id,
                'unit_price'             => 120.00,
                'currency'               => 'PEN',
                'standard_duration_days' => 30,
                'is_active'              => true,
            ]);
        }
    }

    /**
     * Test 1: Service engagements index page loads with operational KPIs.
     */
    public function test_it_can_render_service_engagements_index_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('services.engagements.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.services.engagements.index');
        $response->assertViewHasAll([
            'totalEngagements',
            'inProgressCount',
            'completedCount',
            'totalAmount',
            'totalContractedHours',
            'totalConsumedHours',
            'lowBalanceCount',
        ]);
    }

    /**
     * Test 2: Server-side DataTables provider returns JSON with calculated metrics.
     */
    public function test_it_provides_datatables_json_with_metrics_and_badges(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                   => $this->client->id,
            'technological_service_id'    => $this->techService->id,
            'productive_activity_id'      => $this->activity->id,
            'responsible_user_id'         => $this->specialistUser->id,
            'description'                 => 'Servicio de prueba para DataTables',
            'delivery_modality'           => 'IN_PERSON',
            'contracted_hours'            => 40.00,
            'consumed_hours'              => 10.00,
            'hourly_rate'                 => 100.00,
            'quantity'                    => 1.00,
            'unit_price'                  => 4000.00,
            'total_amount'                => 4000.00,
            'currency'                    => 'PEN',
            'start_date'                  => now()->toDateString(),
            'expected_delivery_date'      => now()->addDays(30)->toDateString(),
            'status'                      => ServiceEngagementStatus::IN_PROGRESS,
            'min_attendance_percent'      => 80.00,
            'low_balance_threshold_hours' => 5.00,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('services.engagements.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'codigo', 'servicio', 'cliente', 'modalidad_plazo', 'bolsa_horas', 'monto', 'estado', 'acciones']
            ]
        ]);
        $response->assertSee($engagement->code);
    }

    /**
     * Test 3: Contract service ad-hoc with modality, hours pool, and hourly rate.
     */
    public function test_it_can_create_ad_hoc_service_engagement(): void
    {
        $payload = [
            'client_id'                   => $this->client->id,
            'technological_service_id'    => $this->techService->id,
            'productive_activity_id'      => $this->activity->id,
            'responsible_user_id'         => $this->specialistUser->id,
            'description'                 => 'Contratación ad-hoc de 50 horas de consultoría y talleres',
            'delivery_modality'           => 'HYBRID',
            'contracted_hours'            => 50.00,
            'hourly_rate'                 => 120.00,
            'quantity'                    => 1.00,
            'unit_price'                  => 6000.00,
            'total_amount'                => 6000.00,
            'currency'                    => 'PEN',
            'start_date'                  => now()->toDateString(),
            'expected_delivery_date'      => now()->addMonths(2)->toDateString(),
            'min_attendance_percent'      => 80.00,
            'low_balance_threshold_hours' => 8.00,
            'allow_pool_overage'          => false,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('service_engagements', [
            'client_id'         => $this->client->id,
            'delivery_modality' => 'HYBRID',
            'contracted_hours'  => 50.00,
            'hourly_rate'       => 120.00,
            'total_amount'      => 6000.00,
            'status'            => 'IN_PROGRESS',
        ]);
    }

    /**
     * Test 4: Service contracting under an agreement enforces agreement budget cap.
     */
    public function test_it_can_create_service_engagement_under_agreement_and_validates_budget_cap(): void
    {
        $agreement = Agreement::create([
            'code'                   => 'CONV-TEST-BUDGET-01',
            'name'                   => 'Convenio Marco de Asistencia Técnica',
            'type'                   => AgreementType::FRAMEWORK,
            'area_id'                => $this->area->id,
            'client_id'              => $this->client->id,
            'responsible_user_id'    => $this->specialistUser->id,
            'coordinator_user_id'    => $this->specialistUser->id,
            'productive_activity_id' => $this->activity->id,
            'start_date'             => now()->toDateString(),
            'end_date'               => now()->addYear()->toDateString(),
            'status'                 => AgreementStatus::ACTIVE,
            'currency'               => 'PEN',
            'total_amount'           => 10000.00, // Budget limit is S/ 10,000.00
        ]);

        // Attempt 1: Service exceeding agreement budget (S/ 15,000 > S/ 10,000)
        $overBudgetPayload = [
            'agreement_id'                => $agreement->id,
            'client_id'                   => $this->client->id,
            'technological_service_id'    => $this->techService->id,
            'productive_activity_id'      => $this->activity->id,
            'responsible_user_id'         => $this->specialistUser->id,
            'description'                 => 'Servicio que excede el techo del convenio',
            'delivery_modality'           => 'FIELD',
            'contracted_hours'            => 100.00,
            'unit_price'                  => 15000.00,
            'total_amount'                => 15000.00,
            'currency'                    => 'PEN',
            'start_date'                  => now()->toDateString(),
            'expected_delivery_date'      => now()->addMonths(1)->toDateString(),
        ];

        $respFail = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.store'), $overBudgetPayload);

        $respFail->assertSessionHasErrors(['total_amount']);

        // Attempt 2: Service within agreement budget (S/ 7,500 <= S/ 10,000)
        $validPayload = $overBudgetPayload;
        $validPayload['unit_price']   = 7500.00;
        $validPayload['total_amount'] = 7500.00;

        $respSuccess = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.store'), $validPayload);

        $respSuccess->assertRedirect();
        $this->assertDatabaseHas('service_engagements', [
            'agreement_id' => $agreement->id,
            'total_amount' => 7500.00,
        ]);
    }

    /**
     * Test 5: Register training sessions with date, topic, instructor, duration, location.
     */
    public function test_it_can_register_training_sessions_with_duration_and_location(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Curso de Poda e Injertación',
            'delivery_modality'        => 'IN_PERSON',
            'contracted_hours'         => 30.00,
            'consumed_hours'           => 0.00,
            'unit_price'               => 3000.00,
            'total_amount'             => 3000.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonth()->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
        ]);

        $sessionPayload = [
            'topic'              => 'Módulo 1: Técnicas Avanzadas de Injertación',
            'instructor_user_id' => $this->specialistUser->id,
            'session_date'       => now()->toDateString(),
            'start_time'         => '08:00',
            'end_time'           => '12:00',
            'duration_hours'     => 4.00,
            'location'           => 'Parcela Demostrativa San Martín',
            'status'             => 'CONDUCTED',
            'observations'       => 'Sesión teórica y práctica en campo.',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.sessions.store', $engagement->id), $sessionPayload);

        $response->assertRedirect();
        $this->assertDatabaseHas('service_sessions', [
            'service_engagement_id' => $engagement->id,
            'topic'                 => 'Módulo 1: Técnicas Avanzadas de Injertación',
            'duration_hours'        => 4.00,
            'location'              => 'Parcela Demostrativa San Martín',
            'status'                => 'CONDUCTED',
        ]);
    }

    /**
     * Test 6: Register participant into engagement and training session.
     */
    public function test_it_can_register_and_list_participants_for_training(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Taller de Catación de Cacao',
            'delivery_modality'        => 'IN_PERSON',
            'contracted_hours'         => 20.00,
            'unit_price'               => 2000.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonth()->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
        ]);

        $session = $engagement->sessions()->create([
            'topic'              => 'Sesión 1: Análisis Sensorial',
            'instructor_user_id' => $this->specialistUser->id,
            'session_date'       => now()->toDateString(),
            'start_time'         => '09:00',
            'end_time'           => '13:00',
            'duration_hours'     => 4.00,
            'location'           => 'Laboratorio de Calidad',
            'status'             => 'SCHEDULED',
        ]);

        $attendeePayload = [
            'dni_or_document'    => '45896321',
            'full_name'          => 'Juan Pérez Gómez',
            'email'              => 'juan.perez@example.com',
            'phone'              => '987123456',
            'organization'       => 'Cooperativa Agraria Central',
            'service_session_id' => $session->id,
            'attended'           => true,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.attendees.store', $engagement->id), $attendeePayload);

        $response->assertRedirect();
        $this->assertDatabaseHas('service_attendees', [
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $session->id,
            'dni_or_document'       => '45896321',
            'full_name'             => 'Juan Pérez Gómez',
            'attended'              => true,
        ]);
    }

    /**
     * Test 7: Excel / CSV import for participants roster.
     */
    public function test_it_can_import_participants_from_csv(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Curso Virtual de Buenas Prácticas Agrícolas',
            'delivery_modality'        => 'VIRTUAL',
            'contracted_hours'         => 16.00,
            'unit_price'               => 1500.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonth()->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
        ]);

        $session = $engagement->sessions()->create([
            'topic'              => 'Sesión Inaugural',
            'instructor_user_id' => $this->specialistUser->id,
            'session_date'       => now()->toDateString(),
            'start_time'         => '10:00',
            'end_time'           => '12:00',
            'duration_hours'     => 2.00,
            'location'           => 'Zoom Online',
            'status'             => 'CONDUCTED',
        ]);

        $csvData = "dni,nombre,email,telefono,organizacion\n" .
            "12345678,Carlos Benites,carlos@example.com,999111222,Asociación de Productores\n" .
            "87654321,María Mendoza,maria@example.com,999333444,Finca San Pedro\n";

        $file = UploadedFile::fake()->createWithContent('participantes.csv', $csvData);

        $response = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.attendees.import', $engagement->id), [
                'excel_file'         => $file,
                'service_session_id' => $session->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('service_attendees', [
            'service_engagement_id' => $engagement->id,
            'dni_or_document'       => '12345678',
            'full_name'             => 'Carlos Benites',
        ]);
        $this->assertDatabaseHas('service_attendees', [
            'service_engagement_id' => $engagement->id,
            'dni_or_document'       => '87654321',
            'full_name'             => 'María Mendoza',
        ]);
    }

    /**
     * Test 8: Attendance percentage calculation across conducted sessions.
     */
    public function test_it_updates_attendance_and_calculates_attendance_percentage(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Programa de Especialización en Fermentación',
            'delivery_modality'        => 'IN_PERSON',
            'contracted_hours'         => 24.00,
            'unit_price'               => 3500.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonths(2)->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
            'min_attendance_percent'   => 80.00,
        ]);

        $session1 = $engagement->sessions()->create([
            'topic'              => 'Sesión 1: Parámetros Fisicoquímicos',
            'instructor_user_id' => $this->specialistUser->id,
            'session_date'       => now()->subDays(5)->toDateString(),
            'start_time'         => '08:00',
            'end_time'           => '12:00',
            'duration_hours'     => 4.00,
            'location'           => 'Aula Magna',
            'status'             => 'CONDUCTED',
        ]);

        $session2 = $engagement->sessions()->create([
            'topic'              => 'Sesión 2: Protocolos de Secado Solar',
            'instructor_user_id' => $this->specialistUser->id,
            'session_date'       => now()->toDateString(),
            'start_time'         => '08:00',
            'end_time'           => '12:00',
            'duration_hours'     => 4.00,
            'location'           => 'Aula Magna',
            'status'             => 'CONDUCTED',
        ]);

        // Participant A: Attended both sessions -> 100%
        $attA1 = ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $session1->id,
            'dni_or_document'       => '11223344',
            'full_name'             => 'Ana Torres',
            'attended'              => true,
        ]);
        $attA2 = ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $session2->id,
            'dni_or_document'       => '11223344',
            'full_name'             => 'Ana Torres',
            'attended'              => true,
        ]);

        // Participant B: Attended only session 1, missed session 2 -> 50%
        $attB1 = ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $session1->id,
            'dni_or_document'       => '55667788',
            'full_name'             => 'Pedro Rojas',
            'attended'              => true,
        ]);
        $attB2 = ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $session2->id,
            'dni_or_document'       => '55667788',
            'full_name'             => 'Pedro Rojas',
            'attended'              => false,
        ]);

        // Verify model methods
        $this->assertEquals(100.0, $engagement->calculateAttendancePercent('11223344'));
        $this->assertTrue($engagement->isEligibleForCertificate('11223344'));

        $this->assertEquals(50.0, $engagement->calculateAttendancePercent('55667788'));
        $this->assertFalse($engagement->isEligibleForCertificate('55667788'));
    }

    /**
     * Test 9: ACCEPTANCE CRITERIA: Certificates are generated ONLY upon meeting configurable minimum attendance threshold.
     */
    public function test_it_blocks_certificate_issuance_when_attendance_is_below_threshold(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Capacitación con umbral de 80%',
            'delivery_modality'        => 'IN_PERSON',
            'contracted_hours'         => 20.00,
            'unit_price'               => 2000.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonth()->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
            'min_attendance_percent'   => 80.00,
        ]);

        // Create 2 conducted sessions
        $s1 = $engagement->sessions()->create([
            'topic' => 'S1', 'instructor_user_id' => $this->specialistUser->id,
            'session_date' => now()->toDateString(), 'start_time' => '08:00', 'end_time' => '10:00',
            'duration_hours' => 2, 'location' => 'Aula 1', 'status' => 'CONDUCTED',
        ]);
        $s2 = $engagement->sessions()->create([
            'topic' => 'S2', 'instructor_user_id' => $this->specialistUser->id,
            'session_date' => now()->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00',
            'duration_hours' => 2, 'location' => 'Aula 1', 'status' => 'CONDUCTED',
        ]);

        // Participant with only 50% attendance (attended 1 of 2)
        $attendee = ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $s1->id,
            'dni_or_document'       => '77889900',
            'full_name'             => 'Inasistente Frecuente',
            'attended'              => true,
        ]);
        ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $s2->id,
            'dni_or_document'       => '77889900',
            'full_name'             => 'Inasistente Frecuente',
            'attended'              => false,
        ]);

        // Attempt to issue certificate via POST JSON
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('services.engagements.certificates.issue', [$engagement->id, $attendee->id]));

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('por debajo del umbral mínimo', $response->json('message'));

        $attendee->refresh();
        $this->assertNull($attendee->certificate_code);
    }

    /**
     * Test 10: Issue certificate when participant meets or exceeds attendance threshold.
     */
    public function test_it_issues_certificate_when_attendance_meets_or_exceeds_threshold(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Capacitación Exitosa',
            'delivery_modality'        => 'IN_PERSON',
            'contracted_hours'         => 20.00,
            'unit_price'               => 2000.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonth()->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
            'min_attendance_percent'   => 80.00,
        ]);

        $s1 = $engagement->sessions()->create([
            'topic' => 'S1', 'instructor_user_id' => $this->specialistUser->id,
            'session_date' => now()->toDateString(), 'start_time' => '08:00', 'end_time' => '10:00',
            'duration_hours' => 2, 'location' => 'Aula 1', 'status' => 'CONDUCTED',
        ]);

        // Participant with 100% attendance
        $attendee = ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $s1->id,
            'dni_or_document'       => '33445566',
            'full_name'             => 'Estudiante Destacado',
            'attended'              => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('services.engagements.certificates.issue', [$engagement->id, $attendee->id]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $attendee->refresh();
        $this->assertNotNull($attendee->certificate_code);
        $this->assertNotNull($attendee->certificate_issued_at);
        $this->assertNotNull($attendee->certificate_hash);
        $this->assertStringStartsWith('CERT-', $attendee->certificate_code);
    }

    /**
     * Test 11: ACCEPTANCE CRITERIA: QR codes allow verification WITHOUT requiring a login.
     */
    public function test_public_qr_code_verification_works_without_requiring_a_login(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Servicio con Certificado Emitido',
            'delivery_modality'        => 'IN_PERSON',
            'contracted_hours'         => 30.00,
            'unit_price'               => 3000.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonth()->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
        ]);

        $session = $engagement->sessions()->create([
            'topic'              => 'Sesión de Certificación',
            'instructor_user_id' => $this->specialistUser->id,
            'session_date'       => now()->toDateString(),
            'start_time'         => '08:00',
            'end_time'           => '12:00',
            'duration_hours'     => 4,
            'location'           => 'Auditorio Principal',
            'status'             => 'CONDUCTED',
        ]);

        $attendee = ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $session->id,
            'dni_or_document'       => '99887766',
            'full_name'             => 'Verificable Público',
            'certificate_code'      => 'CERT-2026-99999',
            'certificate_issued_at' => now(),
            'certificate_hash'      => hash('sha256', 'CERT-2026-99999|99887766|Verificable Público'),
            'attended'              => true,
        ]);

        // Unauthenticated request to the public verification endpoint!
        $response = $this->get(route('services.certificates.verify_public', $attendee->certificate_code));

        $response->assertStatus(200);
        $response->assertViewIs('admin.services.certificates.public_verify');
        $response->assertSee('CERT-2026-99999');
        $response->assertSee('Verificable Público');
        $response->assertSee('99887766');
        $response->assertSee('Certificado Auténtico y Vigente');
    }

    /**
     * Test 12: Generate and stream PDF certificate via DomPDF.
     */
    public function test_it_can_download_pdf_certificate_via_dompdf(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Servicio para emisión de PDF',
            'delivery_modality'        => 'IN_PERSON',
            'contracted_hours'         => 20.00,
            'unit_price'               => 2000.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonth()->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
            'min_attendance_percent'   => 80.00,
        ]);

        $session = $engagement->sessions()->create([
            'topic' => 'Sesión Única', 'instructor_user_id' => $this->specialistUser->id,
            'session_date' => now()->toDateString(), 'start_time' => '08:00', 'end_time' => '12:00',
            'duration_hours' => 4, 'location' => 'Aula 1', 'status' => 'CONDUCTED',
        ]);

        $attendee = ServiceAttendee::create([
            'service_engagement_id' => $engagement->id,
            'service_session_id'    => $session->id,
            'dni_or_document'       => '44556677',
            'full_name'             => 'Graduado DomPDF',
            'certificate_code'      => 'CERT-2026-11111',
            'certificate_issued_at' => now(),
            'certificate_hash'      => hash('sha256', 'CERT-2026-11111|44556677'),
            'attended'              => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('services.engagements.certificates.download', [$engagement->id, $attendee->id]));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->getContent());
    }

    /**
     * Test 13: Technical assistance and consulting hour logs deduct from contracted pool.
     */
    public function test_technical_assistance_hour_log_deducts_time_from_contracted_pool(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Bolsa de 40 horas de asesoría técnica en fincas',
            'delivery_modality'        => 'FIELD',
            'contracted_hours'         => 40.00,
            'consumed_hours'           => 0.00,
            'hourly_rate'              => 80.00,
            'unit_price'               => 3200.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonths(3)->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
            'low_balance_threshold_hours' => 5.00,
        ]);

        $logPayload = [
            'specialist_user_id' => $this->specialistUser->id,
            'log_date'           => now()->toDateString(),
            'hours'              => 6.50,
            'modality'           => 'FIELD',
            'activity_performed' => 'Diagnóstico fitosanitario de moniliasis y calibración de motopulverizadoras',
            'location_or_farm'   => 'Finca La Esmeralda - Parcela 3',
            'client_contact_name'=> 'Don Eulogio Quispe',
            'client_signed'      => true,
            'observations'       => 'Se dejaron recomendaciones por escrito en bitácora de campo.',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.hour_logs.store', $engagement->id), $logPayload);

        $response->assertRedirect();

        $engagement->refresh();
        $this->assertEquals(6.50, (float) $engagement->consumed_hours);
        $this->assertEquals(33.50, $engagement->remainingHours());

        $this->assertDatabaseHas('service_hour_logs', [
            'service_engagement_id' => $engagement->id,
            'specialist_user_id'    => $this->specialistUser->id,
            'hours'                 => 6.50,
            'modality'              => 'FIELD',
            'client_signed'         => true,
        ]);
    }

    /**
     * Test 14: Alert is triggered when remaining hour balance runs low (<= threshold).
     */
    public function test_it_triggers_alert_when_hour_balance_runs_low(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                   => $this->client->id,
            'technological_service_id'    => $this->techService->id,
            'productive_activity_id'      => $this->activity->id,
            'responsible_user_id'         => $this->specialistUser->id,
            'description'                 => 'Bolsa de 20 horas con alerta baja',
            'delivery_modality'           => 'FIELD',
            'contracted_hours'            => 20.00,
            'consumed_hours'              => 0.00,
            'hourly_rate'                 => 100.00,
            'unit_price'                  => 2000.00,
            'currency'                    => 'PEN',
            'start_date'                  => now()->toDateString(),
            'expected_delivery_date'      => now()->addMonths(2)->toDateString(),
            'status'                      => ServiceEngagementStatus::IN_PROGRESS,
            'low_balance_threshold_hours' => 5.00,
        ]);

        // Consume 16 hours -> 4 hours remain (<= 5 hours threshold)
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('services.engagements.hour_logs.store', $engagement->id), [
                'specialist_user_id' => $this->specialistUser->id,
                'log_date'           => now()->toDateString(),
                'hours'              => 16.00,
                'modality'           => 'FIELD',
                'activity_performed' => 'Jornada intensiva de evaluación',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success'        => true,
            'is_low_balance' => true,
            'remaining_hours'=> 4.0,
        ]);
        $this->assertStringContainsString('ALERTA', $response->json('message'));

        $engagement->refresh();
        $this->assertTrue($engagement->isLowBalance());
    }

    /**
     * Test 15: ACCEPTANCE CRITERIA: Consumed hours NEVER exceed the allocated pool without explicit authorization.
     */
    public function test_consumed_hours_never_exceed_allocated_pool_without_explicit_authorization(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                   => $this->client->id,
            'technological_service_id'    => $this->techService->id,
            'productive_activity_id'      => $this->activity->id,
            'responsible_user_id'         => $this->specialistUser->id,
            'description'                 => 'Bolsa estricta de 20 horas',
            'delivery_modality'           => 'FIELD',
            'contracted_hours'            => 20.00,
            'consumed_hours'              => 0.00,
            'hourly_rate'                 => 100.00,
            'unit_price'                  => 2000.00,
            'currency'                    => 'PEN',
            'start_date'                  => now()->toDateString(),
            'expected_delivery_date'      => now()->addMonth()->toDateString(),
            'status'                      => ServiceEngagementStatus::IN_PROGRESS,
            'allow_pool_overage'          => false,
            'low_balance_threshold_hours' => 5.00,
        ]);

        // Prior consumption of 18 hours via hour log
        $engagement->hourLogs()->create([
            'specialist_user_id'    => $this->specialistUser->id,
            'log_date'              => now()->subDays(2)->toDateString(),
            'hours'                 => 18.00,
            'modality'              => 'FIELD',
            'activity_performed'    => 'Servicios técnicos previos acumulados',
            'is_authorized_overage' => false,
        ]);
        $engagement->refresh();
        $this->assertEquals(18.00, (float) $engagement->consumed_hours);

        // Case A: Attempt to log 5 hours without authorization -> must be REJECTED!
        $rejectedPayload = [
            'specialist_user_id'    => $this->specialistUser->id,
            'log_date'              => now()->toDateString(),
            'hours'                 => 5.00, // 18 + 5 = 23 > 20
            'modality'              => 'FIELD',
            'activity_performed'    => 'Visita de campo no autorizada para sobregiro',
            'is_authorized_overage' => false,
        ];

        $respRejected = $this->actingAs($this->adminUser)
            ->postJson(route('services.engagements.hour_logs.store', $engagement->id), $rejectedPayload);

        $respRejected->assertStatus(422);
        $respRejected->assertJson(['success' => false]);
        $this->assertStringContainsString('superan la bolsa de horas contratadas', $respRejected->json('message'));

        $engagement->refresh();
        $this->assertEquals(18.00, (float) $engagement->consumed_hours);

        // Case B: Log with EXPLICIT AUTHORIZATION (is_authorized_overage = true) -> ALLOWED!
        $authorizedPayload = $rejectedPayload;
        $authorizedPayload['is_authorized_overage'] = true;

        $respAuthorized = $this->actingAs($this->adminUser)
            ->postJson(route('services.engagements.hour_logs.store', $engagement->id), $authorizedPayload);

        $respAuthorized->assertStatus(200);
        $respAuthorized->assertJson(['success' => true]);

        $engagement->refresh();
        $this->assertEquals(23.00, (float) $engagement->consumed_hours);
        $this->assertDatabaseHas('service_hour_logs', [
            'service_engagement_id' => $engagement->id,
            'hours'                 => 5.00,
            'is_authorized_overage' => true,
        ]);
    }

    /**
     * Test 16: Deliverables management: file upload, due date, and client sign-off.
     */
    public function test_it_can_upload_deliverable_and_register_client_signoff(): void
    {
        Storage::fake('private');

        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Servicio con Entregables de Diagnóstico',
            'delivery_modality'        => 'FIELD',
            'contracted_hours'         => 30.00,
            'unit_price'               => 3000.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->toDateString(),
            'expected_delivery_date'   => now()->addMonths(2)->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
        ]);

        $file = UploadedFile::fake()->create('informe_diagnostico.pdf', 500, 'application/pdf');

        // Step 1: Upload Deliverable
        $uploadResp = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.deliverables.store', $engagement->id), [
                'deliverable_name' => 'Informe de Diagnóstico Fitosanitario y Plan de Acción',
                'description'      => 'Documento técnico con mapas de incidencia de plagas y recomendaciones.',
                'due_date'         => now()->addDays(15)->toDateString(),
                'file'             => $file,
            ]);

        $uploadResp->assertRedirect();
        $deliverable = ServiceDeliverable::where('service_engagement_id', $engagement->id)->first();
        $this->assertNotNull($deliverable);
        $this->assertEquals(ServiceDeliverableStatus::SUBMITTED, $deliverable->status);
        $this->assertNotNull($deliverable->file_path);

        // Step 2: Client Sign-off
        $signoffResp = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.deliverables.signoff', [$engagement->id, $deliverable->id]), [
                'client_signoff_name'  => 'Ing. Roberto Silva - Gerente Técnico Cliente',
                'client_signoff_notes' => 'Entregable recibido a entera satisfacción en reunión presencial.',
            ]);

        $signoffResp->assertRedirect();
        $deliverable->refresh();
        $this->assertEquals(ServiceDeliverableStatus::APPROVED, $deliverable->status);
        $this->assertEquals('Ing. Roberto Silva - Gerente Técnico Cliente', $deliverable->client_signoff_name);
        $this->assertNotNull($deliverable->client_signoff_date);
    }

    /**
     * Test 17: Service status tracking, formal closure, and final PDF closure report.
     */
    public function test_it_can_close_service_engagement_and_download_final_report_pdf(): void
    {
        $engagement = ServiceEngagement::create([
            'client_id'                => $this->client->id,
            'technological_service_id' => $this->techService->id,
            'productive_activity_id'   => $this->activity->id,
            'responsible_user_id'      => $this->specialistUser->id,
            'description'              => 'Servicio Integral listo para cierre formal',
            'delivery_modality'        => 'FIELD',
            'contracted_hours'         => 40.00,
            'consumed_hours'           => 38.00,
            'unit_price'               => 4500.00,
            'currency'                 => 'PEN',
            'start_date'               => now()->subMonths(2)->toDateString(),
            'expected_delivery_date'   => now()->toDateString(),
            'status'                   => ServiceEngagementStatus::IN_PROGRESS,
        ]);

        // Step 1: Close engagement
        $closeResp = $this->actingAs($this->adminUser)
            ->post(route('services.engagements.close', $engagement->id), [
                'closure_summary'  => 'Se cumplieron satisfactoriamente todas las metas de asistencia técnica y capacitaciones programadas.',
                'settlement_notes' => 'Conformidad técnica firmada sin observaciones.',
            ]);

        $closeResp->assertRedirect();
        $engagement->refresh();
        $this->assertEquals(ServiceEngagementStatus::COMPLETED, $engagement->status);
        $this->assertNotNull($engagement->closed_at);
        $this->assertEquals($this->adminUser->id, $engagement->closed_by_user_id);

        // Step 2: Download Final Report PDF via DomPDF
        $pdfResp = $this->actingAs($this->adminUser)
            ->get(route('services.engagements.final_report', $engagement->id));

        $pdfResp->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResp->headers->get('Content-Type'));
        $this->assertNotEmpty($pdfResp->getContent());
    }
}
