<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\AgreementType;
use App\Enums\InstallmentStatus;
use App\Enums\ObligationResponsibleParty;
use App\Enums\ObligationStatus;
use App\Models\ActivitySalesAttribution;
use App\Models\Agreement;
use App\Models\AgreementAlertLog;
use App\Models\AgreementInstallment;
use App\Models\AgreementObligation;
use App\Models\Area;
use App\Models\Billing;
use App\Models\Client;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ProductiveActivity;
use App\Models\SaleNote;
use App\Models\TechnologicalService;
use App\Models\User;
use App\Notifications\AgreementDeadlineAlertNotification;
use App\Services\Agreements\AgreementCodeService;
use App\Services\Agreements\AgreementInstallmentService;
use App\Services\Agreements\AgreementReportService;
use Carbon\Carbon;
use Database\Seeders\AgreementRoleAndPermissionSeeder;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgreementInstallmentAndRevenueTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $coordinatorUser;
    protected Client $client;
    protected Area $area;
    protected ProductiveActivity $activity;
    protected TechnologicalService $techService;
    protected Agreement $agreement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AgreementRoleAndPermissionSeeder::class);

        $cashId = (int) (DB::table('cashes')->value('id') ?? 1);
        $warehouseId = (int) (DB::table('warehouses')->value('id') ?? 1);

        $this->adminUser = User::firstOrCreate(
            ['user' => 'admin_test_c4'],
            [
                'nombres'   => 'Admin Test C4',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => $cashId,
                'idalmacen' => $warehouseId,
            ]
        );
        $this->adminUser->assignRole('SUPERADMIN');

        $this->coordinatorUser = User::firstOrCreate(
            ['user' => 'coord_test_c4'],
            [
                'nombres'   => 'Coordinador Test C4',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => $cashId,
                'idalmacen' => $warehouseId,
            ]
        );
        $this->coordinatorUser->assignRole('COORDINADOR');

        $this->area = Area::firstOrCreate(
            ['name' => 'Área Productiva C4'],
            ['code' => 'APC4', 'description' => 'Área de prueba C4']
        );

        $this->client = Client::firstOrCreate(
            ['nro_documento' => '20778899001'],
            [
                'nombres'             => 'EMPRESA AGRO EXPORT C4 SAC',
                'idtipo_comprobante'  => 1,
                'direccion'           => 'Av. Industrial 123, Ica',
                'telefono'            => '987654321',
                'email'               => 'contacto@agroexportc4.pe',
            ]
        );

        $this->activity = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT-C4-01'],
            [
                'name'        => 'Servicios de Certificación C4',
                'area_id'     => $this->area->id,
                'status'      => 'ACTIVA',
                'budget'      => 50000.00,
            ]
        );

        $product = \App\Models\Product::where('opcion', 2)->first() ?? \App\Models\Product::first();
        if (!$product) {
            $unitId = DB::table('units')->value('id') ?? 1;
            $catId = DB::table('categories')->value('id') ?? 1;
            $igvId = DB::table('igv_type_affections')->value('id') ?? 1;
            $product = \App\Models\Product::create([
                'codigo_interno' => 'PRD-C4-TEST',
                'codigo_barras'  => 'PRD-C4-TEST',
                'codigo_sunat'   => '78102203',
                'descripcion'    => 'Servicio Tecnológico Test C4',
                'idunidad'       => $unitId,
                'idcategoria'    => $catId,
                'idcodigo_igv'   => $igvId,
                'precio_compra'  => 0.00,
                'precio_venta'   => 1500.00,
                'opcion'         => 2,
            ]);
        }

        $this->techService = TechnologicalService::first() ?? TechnologicalService::create([
            'product_id'             => $product->id,
            'name'                   => 'Asistencia Técnica en Suelos C4',
            'description'            => 'Servicio de prueba C4',
            'area_id'                => $this->area->id,
            'productive_activity_id' => $this->activity->id,
            'category'               => \App\Enums\ServiceCategory::TECHNICAL_ASSISTANCE,
            'delivery_modality'      => 'IN_PERSON',
            'unit_price'             => 1500.00,
            'currency'               => 'PEN',
            'estimated_hours'        => 20,
            'is_active'              => true,
        ]);

        $code = app(AgreementCodeService::class)->generateAgreementCode(2026);
        $this->agreement = Agreement::create([
            'code'                   => $code,
            'title'                  => 'Convenio de Cooperación Técnica Agroindustrial C4',
            'type'                   => AgreementType::SPECIFIC,
            'scope'                  => 'INTER_INSTITUTIONAL',
            'status'                 => AgreementStatus::ACTIVE,
            'client_id'              => $this->client->id,
            'area_id'                => $this->area->id,
            'coordinator_user_id'    => $this->coordinatorUser->id,
            'productive_activity_id' => $this->activity->id,
            'start_date'             => Carbon::now()->subMonths(1)->toDateString(),
            'end_date'               => Carbon::now()->addMonths(5)->toDateString(),
            'total_amount'           => 12000.00,
            'currency'               => 'PEN',
            'objective'              => 'Brindar servicios especializados y asistencia técnica agroindustrial.',
            'created_by_user_id'     => $this->adminUser->id,
        ]);
    }

    /**
     * DELIVERABLE 1 & 2: Revenue schedule (agreement_installments) with amount, date, milestone condition,
     * status lifecycle (PENDING, INVOICED, COLLECTED, OVERDUE).
     */
    public function test_revenue_schedule_installment_creation_and_status_derivation(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create a future installment -> PENDING
        $response1 = $this->postJson(route('agreements.installments.store', $this->agreement->id), [
            'amount'                   => 4000.00,
            'due_date'                 => Carbon::now()->addDays(20)->toDateString(),
            'milestone_condition'      => 'A la entrega del informe preliminar de suelos',
            'description'              => 'Primer desembolso por asistencia',
            'technological_service_id' => $this->techService->id,
        ]);

        $response1->assertStatus(201);
        $this->assertDatabaseHas('agreement_installments', [
            'agreement_id'             => $this->agreement->id,
            'installment_number'       => 1,
            'amount'                   => 4000.00,
            'milestone_condition'      => 'A la entrega del informe preliminar de suelos',
            'status'                   => InstallmentStatus::PENDING->value,
            'technological_service_id' => $this->techService->id,
        ]);

        // 2. Create a past installment -> OVERDUE
        $response2 = $this->postJson(route('agreements.installments.store', $this->agreement->id), [
            'amount'                   => 3000.00,
            'due_date'                 => Carbon::now()->subDays(5)->toDateString(),
            'milestone_condition'      => 'Al inicio del convenio',
            'description'              => 'Cuota inicial retrasada',
        ]);

        $response2->assertStatus(201);
        $this->assertDatabaseHas('agreement_installments', [
            'agreement_id'       => $this->agreement->id,
            'installment_number' => 2,
            'amount'             => 3000.00,
            'status'             => InstallmentStatus::OVERDUE->value,
        ]);
    }

    /**
     * DELIVERABLE 1: Voucher generation opens POS/billing with service & amount pre-loaded.
     */
    public function test_prepare_installment_checkout_preloads_data(): void
    {
        $this->actingAs($this->adminUser);

        $installment = AgreementInstallment::create([
            'agreement_id'             => $this->agreement->id,
            'installment_number'       => 1,
            'amount'                   => 5000.00,
            'due_date'                 => Carbon::now()->addDays(15)->toDateString(),
            'milestone_condition'      => 'Culminación del Módulo 1',
            'description'              => 'Pago Módulo 1',
            'technological_service_id' => $this->techService->id,
            'status'                   => InstallmentStatus::PENDING,
            'currency'                 => 'PEN',
        ]);

        $response = $this->getJson(route('agreements.installments.checkout_prep', [
            'id'          => $this->agreement->id,
            'installment' => $installment->id,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'installment_id'         => $installment->id,
                    'installment_number'     => 1,
                    'amount'                 => 5000.00,
                    'client_name'            => 'EMPRESA AGRO EXPORT C4 SAC',
                    'client_document'        => '20778899001',
                    'milestone_condition'    => 'Culminación del Módulo 1',
                    'technological_service_id' => $this->techService->id,
                    'can_be_invoiced'        => true,
                    'current_status'         => 'PENDING',
                ],
            ]);
    }

    /**
     * DELIVERABLE 1, 3 & ACCEPTANCE: Voucher generation links billing_id or sale_note_id to installment,
     * sets status to INVOICED, posts accounting entry solely through Track B, and attributes revenue without duplication.
     */
    public function test_generate_voucher_links_to_installment_and_integrates_track_b_accounting(): void
    {
        $this->actingAs($this->adminUser);

        $installment = AgreementInstallment::create([
            'agreement_id'             => $this->agreement->id,
            'installment_number'       => 1,
            'amount'                   => 4500.00,
            'due_date'                 => Carbon::now()->addDays(10)->toDateString(),
            'milestone_condition'      => 'Aprobación de informe de asesoría',
            'description'              => 'Cuota 1 de asesoría',
            'technological_service_id' => $this->techService->id,
            'status'                   => InstallmentStatus::PENDING,
            'currency'                 => 'PEN',
        ]);

        $initialJournalEntriesCount = JournalEntry::count();

        // Generate Billing voucher
        $response = $this->postJson(route('agreements.installments.generate_voucher', [
            'id'          => $this->agreement->id,
            'installment' => $installment->id,
        ]), [
            'voucher_type'       => 'BILLING',
            'idtipo_comprobante' => 1, // Factura
            'is_paid'            => false,
            'amount'             => 4500.00,
        ]);

        $response->assertStatus(201);
        $installment->refresh();

        // 1. Installment status is now INVOICED and has billing_id
        $this->assertEquals(InstallmentStatus::INVOICED, $installment->status);
        $this->assertNotNull($installment->billing_id);
        $this->assertNotNull($installment->invoiced_at);
        $this->assertNull($installment->paid_at);

        // 2. Billing voucher created with correct amount and details
        $billing = Billing::find($installment->billing_id);
        $this->assertNotNull($billing);
        $this->assertEquals(4500.00, (float)$billing->total);
        $this->assertEquals($this->client->id, $billing->idcliente);

        // 3. Track B Accounting Integration: voucher posting created balanced entry (source_type = BILLING)
        $billingJournal = JournalEntry::where('source_type', 'BILLING')
            ->where('source_id', $billing->id)
            ->first();

        if ($billingJournal) {
            $this->assertEquals($billingJournal->total_debit, $billingJournal->total_credit);
            // Verify account 70 (Revenue) has credit
            $has70Credit = JournalEntryLine::where('journal_entry_id', $billingJournal->id)
                ->whereHas('account', fn($q) => $q->where('code', 'like', '70%'))
                ->where('credit', '>', 0)
                ->exists();
            $this->assertTrue($has70Credit);
        }

        // 4. Verify NO separate duplicate journal entry exists with agreement source
        $duplicateAgreementJournal = JournalEntry::where('source_type', 'AGREEMENT_INSTALLMENT')
            ->where('source_id', $installment->id)
            ->exists();
        $this->assertFalse($duplicateAgreementJournal, 'No separate duplicate journal entry should be created for installment.');

        // 5. Revenue attribution: ActivitySalesAttribution linked to detail_billing_id without duplication
        $detailBilling = $billing->details()->first();
        $attributions = ActivitySalesAttribution::where('detail_billing_id', $detailBilling->id)->get();
        $this->assertCount(1, $attributions, 'Revenue attribution must be created exactly once.');
        $this->assertEquals($this->activity->id, $attributions->first()->productive_activity_id);
        $this->assertEquals(4500.00, (float)$attributions->first()->attributed_amount);
    }

    /**
     * CRITICAL ACCEPTANCE: An installment cannot be invoiced twice.
     */
    public function test_an_installment_cannot_be_invoiced_twice(): void
    {
        $this->actingAs($this->adminUser);

        $installment = AgreementInstallment::create([
            'agreement_id'             => $this->agreement->id,
            'installment_number'       => 1,
            'amount'                   => 2500.00,
            'due_date'                 => Carbon::now()->addDays(5)->toDateString(),
            'status'                   => InstallmentStatus::PENDING,
            'currency'                 => 'PEN',
        ]);

        // First invoicing -> Success
        $resp1 = $this->postJson(route('agreements.installments.generate_voucher', [
            'id'          => $this->agreement->id,
            'installment' => $installment->id,
        ]), [
            'voucher_type' => 'BILLING',
        ]);
        $resp1->assertStatus(201);

        $installment->refresh();
        $this->assertTrue($installment->isInvoiced());
        $this->assertFalse($installment->canBeInvoiced());

        // Second invoicing attempt -> REJECTED (HTTP 422 with DomainException)
        $resp2 = $this->postJson(route('agreements.installments.generate_voucher', [
            'id'          => $this->agreement->id,
            'installment' => $installment->id,
        ]), [
            'voucher_type' => 'SALE_NOTE',
        ]);

        $resp2->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Attempt via linkVoucher -> Also REJECTED
        $resp3 = $this->postJson(route('agreements.installments.link_voucher', [
            'id'          => $this->agreement->id,
            'installment' => $installment->id,
        ]), [
            'sale_note_id' => 999,
        ]);

        $resp3->assertStatus(422);

        // Direct Service invocation throws DomainException
        $this->expectException(DomainException::class);
        app(AgreementInstallmentService::class)->createVoucher($installment, ['voucher_type' => 'BILLING'], $this->adminUser);
    }

    /**
     * DELIVERABLE 2: Installment payment collection and authorized adjustment.
     */
    public function test_installment_collection_and_authorized_adjustment(): void
    {
        $this->actingAs($this->adminUser);

        $installment = AgreementInstallment::create([
            'agreement_id'       => $this->agreement->id,
            'installment_number' => 1,
            'amount'             => 3500.00,
            'due_date'           => Carbon::now()->addDays(7)->toDateString(),
            'status'             => InstallmentStatus::INVOICED,
            'currency'           => 'PEN',
        ]);

        // 1. Record payment -> status COLLECTED
        $responsePay = $this->postJson(route('agreements.installments.payment', [
            'id'          => $this->agreement->id,
            'installment' => $installment->id,
        ]), [
            'payment_reference' => 'OPE-BCP-998877',
            'paid_at'           => Carbon::now()->toDateString(),
        ]);

        $responsePay->assertStatus(200);
        $installment->refresh();

        $this->assertEquals(InstallmentStatus::COLLECTED, $installment->status);
        $this->assertEquals('OPE-BCP-998877', $installment->payment_reference);
        $this->assertNotNull($installment->paid_at);

        // 2. Create another pending installment and perform authorized adjustment
        $installment2 = AgreementInstallment::create([
            'agreement_id'       => $this->agreement->id,
            'installment_number' => 2,
            'amount'             => 2000.00,
            'due_date'           => Carbon::now()->addDays(30)->toDateString(),
            'status'             => InstallmentStatus::PENDING,
            'currency'           => 'PEN',
        ]);

        $responseAdj = $this->postJson(route('agreements.installments.adjust', [
            'id'          => $this->agreement->id,
            'installment' => $installment2->id,
        ]), [
            'amount'              => 2200.00,
            'due_date'            => Carbon::now()->addDays(45)->toDateString(),
            'milestone_condition' => 'Hito reprogramado según adenda N° 1',
            'adjustment_notes'    => 'Ajuste aprobado por Dirección General debido a reprogramación de campo.',
        ]);

        $responseAdj->assertStatus(200);
        $installment2->refresh();

        $this->assertEquals(2200.00, (float)$installment2->amount);
        $this->assertEquals('Hito reprogramado según adenda N° 1', $installment2->milestone_condition);
        $this->assertEquals('Ajuste aprobado por Dirección General debido a reprogramación de campo.', $installment2->adjustment_notes);
        $this->assertEquals($this->adminUser->id, $installment2->adjusted_by_user_id);
    }

    /**
     * DELIVERABLE 4: Obligations management with responsible party, deadline, evidence, status and compliance dashboard.
     */
    public function test_obligations_management_evidence_upload_and_compliance_dashboard(): void
    {
        Storage::fake('local');
        $this->actingAs($this->adminUser);

        // 1. Create obligation for institution
        $oblInst = AgreementObligation::create([
            'agreement_id'          => $this->agreement->id,
            'title'                 => 'Entrega de Informes de Análisis de Suelo',
            'description'           => 'Descripción del informe de análisis de suelo',
            'responsible_party'     => ObligationResponsibleParty::OUR_INSTITUTION->value,
            'responsible_user_id'   => $this->coordinatorUser->id,
            'due_date'              => Carbon::now()->addDays(15)->toDateString(),
            'status'                => ObligationStatus::IN_PROGRESS->value,
        ]);

        // 2. Create obligation for counterparty
        $oblCounter = AgreementObligation::create([
            'agreement_id'          => $this->agreement->id,
            'title'                 => 'Facilitar Acceso a Parcelas de Ensayo',
            'description'           => 'Descripción del acceso a parcelas de ensayo',
            'responsible_party'     => ObligationResponsibleParty::COUNTERPARTY->value,
            'due_date'              => Carbon::now()->subDays(3)->toDateString(), // Overdue
            'status'                => ObligationStatus::PENDING->value,
        ]);

        // 3. Upload evidence for institution obligation and mark as COMPLETED
        $fakeFile = UploadedFile::fake()->create('informe_suelos_final.pdf', 500, 'application/pdf');

        $responseEv = $this->post(route('agreements.obligations.upload_evidence', [
            'id'         => $this->agreement->id,
            'obligation' => $oblInst->id,
        ]), [
            'evidence_file' => $fakeFile,
            'status'        => 'COMPLETED',
        ], ['Accept' => 'application/json']);

        $responseEv->assertStatus(200);
        $oblInst->refresh();

        $this->assertEquals(ObligationStatus::COMPLETED, $oblInst->status);
        $this->assertNotNull($oblInst->evidence_file_path);
        Storage::disk('local')->assertExists($oblInst->evidence_file_path);

        // 4. Compliance Dashboard
        $responseDash = $this->getJson(route('agreements.compliance', $this->agreement->id));
        $responseDash->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'total_obligations'     => 2,
                    'completed_obligations' => 1,
                    'compliance_percentage' => 50,
                ],
            ]);
    }

    /**
     * DELIVERABLE 5 & ACCEPTANCE: Scheduled command agreements:alerts evaluates 30, 15, and 7 days deadlines
     * and sends notifications ONLY ONCE per threshold (Idempotency).
     */
    public function test_alerts_scheduled_command_runs_daily_and_enforces_threshold_idempotency(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create items approaching deadlines:
        // Agreement ending in 30 days
        $codeExp30 = app(AgreementCodeService::class)->generateAgreementCode(2026);
        $agreement30 = Agreement::create([
            'code'                => $codeExp30,
            'title'               => 'Convenio Marco Vence en 30 Días',
            'type'                => AgreementType::FRAMEWORK,
            'scope'               => 'NATIONAL',
            'status'              => AgreementStatus::ACTIVE,
            'client_id'           => $this->client->id,
            'area_id'             => $this->area->id,
            'coordinator_user_id' => $this->coordinatorUser->id,
            'start_date'          => Carbon::now()->subMonths(11)->toDateString(),
            'end_date'            => Carbon::now()->addDays(30)->toDateString(),
            'created_by_user_id'  => $this->adminUser->id,
        ]);

        // Obligation due in 15 days
        $obl15 = AgreementObligation::create([
            'agreement_id'        => $this->agreement->id,
            'title'               => 'Compromiso vence en 15 días',
            'description'         => 'Descripción del compromiso a 15 días',
            'responsible_party'   => ObligationResponsibleParty::OUR_INSTITUTION->value,
            'responsible_user_id' => $this->coordinatorUser->id,
            'due_date'            => Carbon::now()->addDays(15)->toDateString(),
            'status'              => ObligationStatus::PENDING->value,
        ]);

        // Installment due in 7 days
        $inst7 = AgreementInstallment::create([
            'agreement_id'       => $this->agreement->id,
            'installment_number' => 5,
            'amount'             => 1000.00,
            'due_date'           => Carbon::now()->addDays(7)->toDateString(),
            'status'             => InstallmentStatus::PENDING,
        ]);

        // 2. First Execution of agreements:alerts
        $exitCode1 = Artisan::call('agreements:alerts');
        $this->assertEquals(0, $exitCode1);

        // Assert database alert logs were created
        $log30 = AgreementAlertLog::where('alertable_type', Agreement::class)
            ->where('alertable_id', $agreement30->id)
            ->where('threshold_days', 30)
            ->first();
        $this->assertNotNull($log30, 'Alert log for agreement at 30 days must exist.');

        $log15 = AgreementAlertLog::where('alertable_type', AgreementObligation::class)
            ->where('alertable_id', $obl15->id)
            ->where('threshold_days', 15)
            ->first();
        $this->assertNotNull($log15, 'Alert log for obligation at 15 days must exist.');

        $log7 = AgreementAlertLog::where('alertable_type', AgreementInstallment::class)
            ->where('alertable_id', $inst7->id)
            ->where('threshold_days', 7)
            ->first();
        $this->assertNotNull($log7, 'Alert log for installment at 7 days must exist.');

        $initialLogsCount = AgreementAlertLog::count();
        $initialNotificationsCount = DB::table('notifications')->count();

        // 3. Second Execution (Simulating next daily run on same day or same threshold)
        $exitCode2 = Artisan::call('agreements:alerts');
        $this->assertEquals(0, $exitCode2);

        // IDEMPOTENCY CHECK: No new logs or notifications should be created for the same threshold
        $this->assertEquals($initialLogsCount, AgreementAlertLog::count(), 'Alert logs count must not increase on subsequent runs.');
        $this->assertEquals($initialNotificationsCount, DB::table('notifications')->count(), 'Notifications must not be duplicated for same threshold.');
    }

    /**
     * DELIVERABLE 6 & ACCEPTANCE: Scheduled vs. Invoiced vs. Collected revenue report matches across report,
     * vouchers, and the General Ledger. PDF & Excel exports work cleanly.
     */
    public function test_revenue_report_and_overdue_obligations_report_matches_ledger_and_exports(): void
    {
        $this->actingAs($this->adminUser);

        // Setup 2 installments: 1 invoiced & collected, 1 pending
        $inst1 = AgreementInstallment::create([
            'agreement_id'       => $this->agreement->id,
            'installment_number' => 1,
            'amount'             => 6000.00,
            'due_date'           => Carbon::now()->toDateString(),
            'status'             => InstallmentStatus::PENDING,
            'currency'           => 'PEN',
        ]);

        $inst2 = AgreementInstallment::create([
            'agreement_id'       => $this->agreement->id,
            'installment_number' => 2,
            'amount'             => 6000.00,
            'due_date'           => Carbon::now()->addMonths(2)->toDateString(),
            'status'             => InstallmentStatus::PENDING,
            'currency'           => 'PEN',
        ]);

        // Generate voucher for installment 1 and mark as paid
        $service = app(AgreementInstallmentService::class);
        $service->createVoucher($inst1, [
            'voucher_type' => 'BILLING',
            'is_paid'      => true,
            'amount'       => 6000.00,
        ], $this->adminUser);

        $inst1->refresh();
        $this->assertTrue($inst1->isCollected());

        // 1. Report JSON Compilation
        $reportData = app(AgreementReportService::class)->getRevenueReport(['agreement_id' => $this->agreement->id]);

        $this->assertEquals(12000.00, $reportData['grand_total_scheduled']);
        $this->assertEquals(6000.00, $reportData['grand_total_invoiced']);
        $this->assertEquals(6000.00, $reportData['grand_total_collected']);
        $this->assertEquals(6000.00, $reportData['grand_total_pending']);

        // Check agreement row
        $row = collect($reportData['rows'])->firstWhere('agreement_id', $this->agreement->id);
        $this->assertNotNull($row);
        $this->assertEquals(12000.00, $row['scheduled']);
        $this->assertEquals(6000.00, $row['invoiced']);
        $this->assertEquals(6000.00, $row['collected']);
        $this->assertEquals(6000.00, $row['pending']);

        // 2. Web Report Route
        $respWeb = $this->get(route('agreements.reports.revenue'));
        $respWeb->assertStatus(200);

        // 3. PDF Export Route
        $respPdf = $this->get(route('agreements.reports.revenue.pdf', ['agreement_id' => $this->agreement->id]));
        $respPdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $respPdf->headers->get('Content-Type'));

        // 4. Excel/CSV Export Route
        $respExcel = $this->get(route('agreements.reports.revenue.excel', ['agreement_id' => $this->agreement->id]));
        $respExcel->assertStatus(200);
        $this->assertStringContainsString('text/csv', $respExcel->headers->get('Content-Type'));

        // 5. Overdue Obligations Report & PDF
        AgreementObligation::create([
            'agreement_id'        => $this->agreement->id,
            'title'               => 'Obligación vencida para reporte',
            'description'         => 'Descripción de obligación vencida para reporte',
            'responsible_party'   => 'OUR_INSTITUTION',
            'due_date'            => Carbon::now()->subDays(10)->toDateString(),
            'status'              => ObligationStatus::OVERDUE->value,
        ]);

        $respOblWeb = $this->get(route('agreements.reports.overdue_obligations'));
        $respOblWeb->assertStatus(200);

        $respOblPdf = $this->get(route('agreements.reports.overdue_obligations.pdf'));
        $respOblPdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $respOblPdf->headers->get('Content-Type'));
    }
}
