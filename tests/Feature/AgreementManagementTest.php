<?php

namespace Tests\Feature;

use App\Enums\AddendumType;
use App\Enums\AgreementStatus;
use App\Enums\AgreementType;
use App\Enums\ObligationStatus;
use App\Models\Agreement;
use App\Models\AgreementAddendum;
use App\Models\AgreementAuditLog;
use App\Models\AgreementObligation;
use App\Models\Area;
use App\Models\Client;
use App\Models\DocumentApproval;
use App\Models\EmployeeAreaDetail;
use App\Models\ProductiveActivity;
use App\Models\User;
use App\Services\Agreements\AgreementCodeService;
use App\Services\DocumentApprovalService;
use Carbon\Carbon;
use Database\Seeders\AgreementRoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgreementManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $directorUser;
    protected User $accountingUser;
    protected User $deptAHead;
    protected User $deptBHead;
    protected User $coordinatorUser;
    protected Area $areaA;
    protected Area $areaB;
    protected Client $clientWithRuc;
    protected Client $clientWithoutRuc;
    protected Agreement $agreementDeptA;

    protected function createTestUser(string $username, string $nombres, string $role): User
    {
        $cashId = (int) (DB::table('cashes')->value('id') ?? 1);
        $warehouseId = (int) (DB::table('warehouses')->value('id') ?? 1);

        $user = User::firstOrCreate(
            ['user' => $username],
            [
                'nombres'   => $nombres,
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => $cashId,
                'idalmacen' => $warehouseId,
            ]
        );
        $user->assignRole($role);
        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Roles and Permissions
        $this->seed(AgreementRoleAndPermissionSeeder::class);

        // 2. Setup Areas
        $this->areaA = Area::firstOrCreate(['code' => 'TEST_AREA_A'], [
            'name' => 'Departamento de Agronomía Test',
            'status' => 'ACTIVO',
        ]);
        $this->areaB = Area::firstOrCreate(['code' => 'TEST_AREA_B'], [
            'name' => 'Departamento de Mecánica Test',
            'status' => 'ACTIVO',
        ]);

        $this->adminUser = $this->createTestUser('test_admin_mgmt', 'Admin Sistema', 'ADMIN');
        $this->directorUser = $this->createTestUser('test_director_mgmt', 'Director General Test', 'DIRECTOR_GENERAL');
        $this->accountingUser = $this->createTestUser('test_account_mgmt', 'Contador Test', 'CONTABILIDAD');
        $this->deptAHead = $this->createTestUser('test_dept_a_head', 'Jefe Dept A', 'JEFE_AREA');
        $this->areaA->update(['head_user_id' => $this->deptAHead->id]);

        $this->deptBHead = $this->createTestUser('test_dept_b_head', 'Jefe Dept B', 'JEFE_AREA');
        $this->areaB->update(['head_user_id' => $this->deptBHead->id]);

        $this->coordinatorUser = $this->createTestUser('test_coord_mgmt', 'Coordinador Test', 'COORDINADOR');
        EmployeeAreaDetail::firstOrCreate([
            'user_id' => $this->coordinatorUser->id,
            'area_id' => $this->areaA->id,
        ], [
            'cargo' => 'Coordinador de Convenios',
            'is_primary' => true,
        ]);

        // 4. Setup Clients
        $this->clientWithRuc = Client::create([
            'iddoc' => 2, // RUC
            'nro_documento' => '20123456789',
            'nombres' => 'EMPRESA AGROPECUARIA NACIONAL S.A.C.',
            'direccion' => 'Av. Industrial 456',
        ]);

        $this->clientWithoutRuc = Client::create([
            'iddoc' => 1,
            'nro_documento' => '',
            'nombres' => 'CLIENTE SIN DOCUMENTO TEST',
            'direccion' => 'Calle Principal 123',
        ]);

        // 5. Create an initial Agreement in Dept A
        $this->agreementDeptA = Agreement::create([
            'code' => 'CONV-2026-TEST1',
            'type' => AgreementType::FRAMEWORK,
            'scope' => 'NATIONAL',
            'name' => 'Convenio Marco de Investigación Dept A',
            'objective' => 'Desarrollar proyectos conjuntos de investigación agronómica.',
            'client_id' => $this->clientWithRuc->id,
            'area_id' => $this->areaA->id,
            'coordinator_user_id' => $this->coordinatorUser->id,
            'created_by_user_id' => $this->deptAHead->id,
            'start_date' => Carbon::now()->subMonths(2)->toDateString(),
            'end_date' => Carbon::now()->addMonths(10)->toDateString(),
            'currency' => 'PEN',
            'total_amount' => 50000.00,
            'status' => AgreementStatus::DRAFT,
        ]);
    }

    /**
     * Test 1: Users with broad scope (ADMIN, DIRECTOR_GENERAL, CONTABILIDAD) can view agreements.
     */
    public function test_broad_scope_users_can_view_any_agreement_and_kpis(): void
    {
        // Admin
        $responseAdmin = $this->actingAs($this->adminUser)->get(route('agreements.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Convenios Institucionales');

        // Director General
        $responseDirector = $this->actingAs($this->directorUser)->get(route('agreements.show', $this->agreementDeptA->id));
        $responseDirector->assertOk();
        $responseDirector->assertSee($this->agreementDeptA->code);

        // Accounting
        $responseAccounting = $this->actingAs($this->accountingUser)->get(route('agreements.show', $this->agreementDeptA->id));
        $responseAccounting->assertOk();
        $responseAccounting->assertSee($this->agreementDeptA->code);
    }

    /**
     * Test 2: Users from other departments CANNOT view agreements outside their scope (403 Forbidden).
     */
    public function test_users_from_other_departments_cannot_view_agreements_outside_scope(): void
    {
        // Dept B Head tries to access an agreement of Dept A
        $response = $this->actingAs($this->deptBHead)->get(route('agreements.show', $this->agreementDeptA->id));
        $response->assertStatus(403);

        // Dept B Head DataTables listing should NOT show Dept A agreement
        $dataResponse = $this->actingAs($this->deptBHead)->getJson(route('agreements.data'));
        $dataResponse->assertOk();
        $records = $dataResponse->json('data') ?? [];
        $ids = collect($records)->pluck('id')->filter()->toArray();
        $this->assertNotContains($this->agreementDeptA->id, $ids);
    }

    /**
     * Test 3: Coordinator and department head CAN view agreements in their scope.
     */
    public function test_coordinator_and_department_head_can_view_agreements_in_their_scope(): void
    {
        // Department A Head
        $responseHead = $this->actingAs($this->deptAHead)->get(route('agreements.show', $this->agreementDeptA->id));
        $responseHead->assertOk();
        $responseHead->assertSee($this->agreementDeptA->name);

        // Coordinator
        $responseCoord = $this->actingAs($this->coordinatorUser)->get(route('agreements.show', $this->agreementDeptA->id));
        $responseCoord->assertOk();
        $responseCoord->assertSee($this->agreementDeptA->name);
    }

    /**
     * Test 4: Agreement creation requires valid client with Tax ID / RUC and creates initial audit log.
     */
    public function test_agreement_crud_operations_with_validation_and_audit_logging(): void
    {
        // Attempt creation with invalid/empty RUC client -> should fail validation
        $invalidPayload = [
            'name' => 'Convenio con cliente inválido',
            'type' => 'FRAMEWORK',
            'scope' => 'NATIONAL',
            'objective' => 'Objetivo de prueba',
            'client_id' => $this->clientWithoutRuc->id,
            'area_id' => $this->areaA->id,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addYear()->toDateString(),
            'currency' => 'PEN',
            'total_amount' => 10000.00,
        ];

        $responseInvalid = $this->actingAs($this->adminUser)->post(route('agreements.store'), $invalidPayload);
        $responseInvalid->assertSessionHasErrors(['client_id']);

        // Valid creation with RUC client
        $validPayload = [
            'name' => 'Convenio Específico de Capacitación Técnica',
            'type' => 'SPECIFIC',
            'scope' => 'REGIONAL',
            'objective' => 'Capacitar a técnicos locales en sistemas de riego tecnificado.',
            'client_id' => $this->clientWithRuc->id,
            'area_id' => $this->areaA->id,
            'coordinator_user_id' => $this->coordinatorUser->id,
            'parent_agreement_id' => $this->agreementDeptA->id,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addMonths(6)->toDateString(),
            'currency' => 'PEN',
            'total_amount' => 25000.00,
            'key_clauses' => 'Cláusula Primera: Impartir 10 talleres teóricos y prácticos.',
        ];

        $responseValid = $this->actingAs($this->adminUser)->post(route('agreements.store'), $validPayload);
        $responseValid->assertRedirect();

        $created = Agreement::where('title', 'Convenio Específico de Capacitación Técnica')->first();
        $this->assertNotNull($created);
        $this->assertEquals(AgreementStatus::DRAFT, $created->status);
        $this->assertEquals($this->clientWithRuc->id, $created->client_id);
        $this->assertStringStartsWith('CONV-', $created->code);

        // Verify audit log created
        $auditLog = AgreementAuditLog::where('agreement_id', $created->id)
            ->where('action', 'AGREEMENT_CREATED')
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('DRAFT', $auditLog->new_status);
    }

    /**
     * Test 5: Approval workflow via DocumentApprovalService (type 12: Agreement).
     * Status does NOT switch to ACTIVE without final approval from Director General.
     */
    public function test_approval_workflow_requires_final_step_to_switch_status_to_active(): void
    {
        $agreement = $this->agreementDeptA;
        $this->assertEquals(AgreementStatus::DRAFT, $agreement->status);

        // 1. Submit for approval
        $responseSubmit = $this->actingAs($this->deptAHead)->post(route('agreements.submit_approval', $agreement->id));
        $responseSubmit->assertRedirect();

        $agreement->refresh();
        $this->assertEquals(AgreementStatus::IN_APPROVAL, $agreement->status);

        // Verify 4 steps generated
        $approvals = DocumentApproval::where('document_type', Agreement::class)
            ->where('document_id', $agreement->id)
            ->orderBy('step_order')
            ->get();

        $this->assertCount(4, $approvals);
        // Step 1: SOLICITANTE is auto-approved
        $this->assertEquals('APROBADO', $approvals[0]->status);
        $this->assertEquals('PENDIENTE', $approvals[1]->status);
        $this->assertEquals('PENDIENTE', $approvals[2]->status);
        $this->assertEquals('PENDIENTE', $approvals[3]->status);

        // Status is still IN_APPROVAL (NOT ACTIVE!)
        $this->assertNotEquals(AgreementStatus::ACTIVE, $agreement->status);

        // 2. Step 2 approved by Area Head
        $approvalService = app(DocumentApprovalService::class);
        $approvalService->signAndApprove($approvals[1], $this->deptAHead, null, 'Conforme con los términos técnicos.');

        $agreement->refresh();
        // Status remains IN_APPROVAL
        $this->assertEquals(AgreementStatus::IN_APPROVAL, $agreement->status);

        // 3. Step 3 approved by Administration
        $adminHead = $this->createTestUser('test_adm_step3', 'Jefe Administracion', 'ADMINISTRACION');
        $approvalService->signAndApprove($approvals[2], $adminHead, null, 'Conformidad presupuestal y administrativa.');

        $agreement->refresh();
        // Status still remains IN_APPROVAL
        $this->assertEquals(AgreementStatus::IN_APPROVAL, $agreement->status);

        // 4. Step 4 (Final Step) approved by Director General
        $approvalService->signAndApprove($approvals[3], $this->directorUser, null, 'Aprobación institucional definitiva.');

        $agreement->refresh();
        // NOW and only now, status is ACTIVE!
        $this->assertEquals(AgreementStatus::ACTIVE, $agreement->status);

        // Verify audit log for final approval
        $finalAudit = AgreementAuditLog::where('agreement_id', $agreement->id)
            ->where('action', 'FINAL_APPROVAL')
            ->first();
        $this->assertNotNull($finalAudit);
        $this->assertEquals('ACTIVE', $finalAudit->new_status);
        $this->assertEquals($this->directorUser->id, $finalAudit->user_id);
    }

    /**
     * Test 6: Addenda updates validity period, amount, preserves history and recalculates status.
     */
    public function test_addendum_updates_validity_period_preserves_history_and_recalculates_status(): void
    {
        $agreement = $this->agreementDeptA;
        // Set agreement as EXPIRING_SOON (expiring in 5 days)
        $agreement->update([
            'status' => AgreementStatus::EXPIRING_SOON,
            'end_date' => Carbon::now()->addDays(5)->toDateString(),
            'total_amount' => 50000.00,
        ]);

        $previousEndDate = $agreement->end_date->format('Y-m-d');
        $previousAmount = (float) $agreement->total_amount;
        $newEndDate = Carbon::now()->addMonths(12)->toDateString();

        // Create addendum extending term by 1 year and adding S/ 15,000
        $addendumPayload = [
            'type' => 'MIXED',
            'resolution_number' => 'R.D. N° 099-2026-FVC',
            'justification' => 'Ampliación de 12 meses y adición presupuestal para fase 2.',
            'new_end_date' => $newEndDate,
            'amount_delta' => 15000.00,
            'signature_date' => Carbon::now()->toDateString(),
        ];

        $response = $this->actingAs($this->adminUser)->post(route('agreements.addenda.store', $agreement->id), $addendumPayload);
        $response->assertRedirect();

        $addendum = AgreementAddendum::where('agreement_id', $agreement->id)->first();
        $this->assertNotNull($addendum);

        // Check history preservation on the addendum record
        $this->assertEquals($previousEndDate, $addendum->previous_end_date->format('Y-m-d'));
        $this->assertEquals($previousAmount, (float) $addendum->previous_total_amount);
        $this->assertEquals($newEndDate, $addendum->new_end_date->format('Y-m-d'));
        $this->assertEquals(65000.00, (float) $addendum->new_total_amount);
        $this->assertStringStartsWith("ADD-{$agreement->code}-", $addendum->code);

        // Check Agreement updated validity period and amount
        $agreement->refresh();
        $this->assertEquals($newEndDate, $agreement->end_date->format('Y-m-d'));
        $this->assertEquals(65000.00, (float) $agreement->total_amount);

        // Check status recalculation: with 12 months remaining (> 30 days), status returns to ACTIVE!
        $this->assertEquals(AgreementStatus::ACTIVE, $agreement->status);

        // Check audit log for addendum application
        $addendumAudit = AgreementAuditLog::where('agreement_id', $agreement->id)
            ->where('action', 'ADDENDUM_APPLIED')
            ->first();
        $this->assertNotNull($addendumAudit);
        $this->assertStringContainsString('Adenda', $addendumAudit->reason);
    }

    /**
     * Test 7: Obligations management and progress calculation.
     */
    public function test_obligations_management_and_completion_progress(): void
    {
        $agreement = $this->agreementDeptA;

        // Store 2 obligations
        $this->actingAs($this->deptAHead)->post(route('agreements.obligations.store', $agreement->id), [
            'clause_reference' => 'Tercera',
            'responsible_party' => 'OUR_INSTITUTION',
            'title' => 'Entregar informe semestral de avance',
            'description' => 'Elaborar informe técnico con firma de directores.',
            'due_date' => Carbon::now()->addMonths(3)->toDateString(),
        ]);

        $this->actingAs($this->deptAHead)->post(route('agreements.obligations.store', $agreement->id), [
            'clause_reference' => 'Cuarta',
            'responsible_party' => 'COUNTERPARTY',
            'title' => 'Efectuar transferencia de equipamiento',
            'description' => 'Transferir 2 microscopios al laboratorio.',
            'due_date' => Carbon::now()->addMonths(4)->toDateString(),
        ]);

        $obligations = AgreementObligation::where('agreement_id', $agreement->id)->get();
        $this->assertCount(2, $obligations);

        // Complete 1 obligation
        $ob1 = $obligations[0];
        $response = $this->actingAs($this->adminUser)->postJson(
            route('agreements.obligations.update_status', [$agreement->id, $ob1->id]),
            [
                'status' => 'COMPLETED',
                'evidence_notes' => 'Informe técnico N° 012-2026 entregado en mesa de partes.',
            ]
        );
        $response->assertOk();

        $ob1->refresh();
        $this->assertEquals(ObligationStatus::COMPLETED, $ob1->status);
        $this->assertNotNull($ob1->completed_at);
        $this->assertEquals($this->adminUser->id, $ob1->verified_by_user_id);
    }

    /**
     * Test 8: Manual status change with mandatory reason and audit log.
     */
    public function test_manual_status_change_records_audit_trail(): void
    {
        $agreement = $this->agreementDeptA;

        $response = $this->actingAs($this->adminUser)->post(
            route('agreements.change_status', $agreement->id),
            [
                'new_status' => 'SETTLED',
                'reason' => 'Liquidación final del convenio con acta de conformidad mutua.',
            ]
        );
        $response->assertRedirect();

        $agreement->refresh();
        $this->assertEquals(AgreementStatus::SETTLED, $agreement->status);

        $auditLog = AgreementAuditLog::where('agreement_id', $agreement->id)
            ->where('action', 'STATUS_CHANGE_MANUAL')
            ->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('SETTLED', $auditLog->new_status);
        $this->assertEquals('Liquidación final del convenio con acta de conformidad mutua.', $auditLog->reason);
        $this->assertEquals($this->adminUser->id, $auditLog->user_id);
    }
}
