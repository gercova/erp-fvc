<?php

namespace Tests\Feature;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\Budget;
use App\Models\BudgetApproval;
use App\Models\BudgetLine;
use App\Models\BudgetModification;
use App\Models\ChartOfAccount;
use App\Models\DocumentApproval;
use App\Models\FundSource;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ProductiveActivity;
use App\Models\Requisition;
use App\Models\User;
use App\Notifications\BudgetThresholdExceededNotification;
use App\Services\Budget\BudgetService;
use App\Services\DocumentApprovalService;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $contabilidadUser;
    protected User $administracionUser;
    protected User $directorGeneralUser;

    protected BudgetService $budgetService;
    protected DocumentApprovalService $approvalService;

    protected AccountingPeriod $period;
    protected ProductiveActivity $activityGavilan;
    protected ProductiveActivity $activityVivero;
    protected FundSource $fundRdr;

    protected ChartOfAccount $accExpenses60;
    protected ChartOfAccount $accExpenses63;
    protected ChartOfAccount $accExpenses65;
    protected ChartOfAccount $accCashBank;
    protected ChartOfAccount $accPayables;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approvalService = app(DocumentApprovalService::class);
        $this->budgetService   = app(BudgetService::class);

        // Permissions
        $viewPerm   = Permission::firstOrCreate(['name' => 'accounting.view'], ['descripcion' => 'Ver contabilidad']);
        $createPerm = Permission::firstOrCreate(['name' => 'accounting.create'], ['descripcion' => 'Crear contabilidad']);
        $exportPerm = Permission::firstOrCreate(['name' => 'accounting.export'], ['descripcion' => 'Exportar contabilidad']);

        // Roles
        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleAdmin->syncPermissions([$viewPerm, $createPerm, $exportPerm]);

        $roleCont = Role::firstOrCreate(['name' => 'CONTABILIDAD']);
        $roleCont->syncPermissions([$viewPerm, $createPerm, $exportPerm]);

        $roleAdm = Role::firstOrCreate(['name' => 'ADMINISTRACION']);
        $roleAdm->syncPermissions([$viewPerm, $createPerm, $exportPerm]);

        $roleDg = Role::firstOrCreate(['name' => 'DIRECTOR_GENERAL']);
        $roleDg->syncPermissions([$viewPerm, $createPerm, $exportPerm]);

        // Users
        $this->adminUser = User::firstOrCreate(
            ['user' => 'admin_budget_test'],
            [
                'nombres'   => 'Administrador Budget Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->adminUser->syncRoles(['ADMIN']);
        $this->adminUser->syncPermissions([$viewPerm, $createPerm, $exportPerm]);

        $this->contabilidadUser = User::firstOrCreate(
            ['user' => 'cont_budget_test'],
            [
                'nombres'   => 'Jefe Presupuesto Contabilidad',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->contabilidadUser->syncRoles(['CONTABILIDAD']);

        $this->administracionUser = User::firstOrCreate(
            ['user' => 'adm_budget_test'],
            [
                'nombres'   => 'Jefe Administracion Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->administracionUser->syncRoles(['ADMINISTRACION']);

        $this->directorGeneralUser = User::firstOrCreate(
            ['user' => 'dg_budget_test'],
            [
                'nombres'   => 'Director General Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->directorGeneralUser->syncRoles(['DIRECTOR_GENERAL']);

        // Accounting Period
        $this->period = AccountingPeriod::firstOrCreate(
            ['period_code' => '2025-05'],
            [
                'fiscal_year' => 2025,
                'month'       => 5,
                'name'        => 'Mayo 2025',
                'start_date'  => '2025-05-01',
                'end_date'    => '2025-05-31',
                'status'      => 'OPEN',
            ]
        );

        // Productive Activities
        $this->activityGavilan = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT-TEST-GAVILAN'],
            [
                'name'             => 'Fundo Gavilán Palma Test',
                'type'             => 'AGRICULTURAL',
                'cost_center_code' => 'CC-TEST-GAV',
                'status'           => 'ACTIVA',
                'order_index'      => 1,
            ]
        );

        $this->activityVivero = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT-TEST-VIVERO'],
            [
                'name'             => 'Vivero Agroforestal Test',
                'type'             => 'AGRICULTURAL',
                'cost_center_code' => 'CC-TEST-VIV',
                'status'           => 'ACTIVA',
                'order_index'      => 2,
            ]
        );

        // Funding Source
        $this->fundRdr = FundSource::firstOrCreate(
            ['code' => 'FTE-RDR-TEST'],
            [
                'name'            => 'Recursos Directamente Recaudados Test',
                'current_balance' => 100000.00,
                'initial_balance' => 100000.00,
            ]
        );

        // Accounts
        $this->accExpenses60 = ChartOfAccount::firstOrCreate(
            ['code' => '60111'],
            [
                'name'              => 'Mercaderías manufacturadas Test',
                'element'           => 6,
                'nature'            => AccountNature::DEBIT,
                'account_type'      => AccountType::GASTOS_NATURALEZA,
                'level'             => 5,
                'allows_movement'   => true,
                'requires_cost_center' => true,
                'is_active'         => true,
            ]
        );

        $this->accExpenses63 = ChartOfAccount::firstOrCreate(
            ['code' => '63111'],
            [
                'name'              => 'Servicios de Terceros Test',
                'element'           => 6,
                'nature'            => AccountNature::DEBIT,
                'account_type'      => AccountType::GASTOS_NATURALEZA,
                'level'             => 5,
                'allows_movement'   => true,
                'requires_cost_center' => true,
                'is_active'         => true,
            ]
        );

        $this->accExpenses65 = ChartOfAccount::firstOrCreate(
            ['code' => '65111'],
            [
                'name'              => 'Otros Gastos de Gestión Test',
                'element'           => 6,
                'nature'            => AccountNature::DEBIT,
                'account_type'      => AccountType::GASTOS_NATURALEZA,
                'level'             => 5,
                'allows_movement'   => true,
                'requires_cost_center' => true,
                'is_active'         => true,
            ]
        );

        $this->accPayables = ChartOfAccount::firstOrCreate(
            ['code' => '42121'],
            [
                'name'              => 'Facturas Emitidas Proveedores Test',
                'element'           => 4,
                'nature'            => AccountNature::CREDIT,
                'account_type'      => AccountType::PASIVO,
                'level'             => 5,
                'allows_movement'   => true,
                'is_active'         => true,
            ]
        );

        $this->accCashBank = ChartOfAccount::firstOrCreate(
            ['code' => '10411'],
            [
                'name'              => 'Cuentas Corrientes Banco de la Nación Test',
                'element'           => 1,
                'nature'            => AccountNature::DEBIT,
                'account_type'      => AccountType::ACTIVO,
                'level'             => 5,
                'allows_movement'   => true,
                'is_active'         => true,
            ]
        );
    }

    /**
     * Test 1: Creación de Presupuesto y Líneas con Recálculo de Totales
     */
    public function test_budget_creation_and_lines_initialization(): void
    {
        $budget = $this->budgetService->createBudget([
            'fiscal_year'                => 2025,
            'name'                       => 'Presupuesto Institucional IESTP FVC 2025',
            'code'                       => 'PIM-2025-TEST-01',
            'alert_threshold_percentage' => 85.00,
            'notes'                      => 'Presupuesto anual base para validación',
        ], $this->adminUser);

        $this->assertInstanceOf(Budget::class, $budget);
        $this->assertEquals(2025, $budget->fiscal_year);
        $this->assertEquals('DRAFT', $budget->status);
        $this->assertEquals(85.00, (float) $budget->alert_threshold_percentage);

        // Agregar 3 partidas
        $line1 = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id'    => $this->accExpenses60->id,
            'productive_activity_id' => $this->activityGavilan->id,
            'fund_source_id'         => $this->fundRdr->id,
            'period_month'           => 5,
            'allocated_amount'       => 15000.00,
            'category_name'          => 'Bienes y Suministros',
        ]);

        $line2 = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id'    => $this->accExpenses63->id,
            'productive_activity_id' => $this->activityGavilan->id,
            'fund_source_id'         => $this->fundRdr->id,
            'period_month'           => 5,
            'allocated_amount'       => 25000.00,
            'category_name'          => 'Servicios Especializados',
        ]);

        $line3 = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id'    => $this->accExpenses65->id,
            'productive_activity_id' => $this->activityVivero->id,
            'fund_source_id'         => $this->fundRdr->id,
            'period_month'           => 6,
            'allocated_amount'       => 10000.00,
            'category_name'          => 'Gastos Diversos de Vivero',
        ]);

        $budget->refresh();
        $this->assertCount(3, $budget->lines);
        $this->assertEquals(50000.00, (float) $budget->initial_total_amount);
        $this->assertEquals(50000.00, (float) $budget->current_total_amount);
        $this->assertEquals(15000.00, (float) $line1->current_amount);
    }

    /**
     * Test 2: Modificaciones Presupuestales: Ampliación, Reducción y Transferencia con Auditoría
     */
    public function test_budget_modifications_allocations_cancellations_and_transfers(): void
    {
        $budget = $this->budgetService->createBudget(['fiscal_year' => 2025], $this->adminUser);

        $lineA = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id' => $this->accExpenses60->id,
            'period_month'        => 5,
            'allocated_amount'    => 20000.00,
        ]);

        $lineB = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id' => $this->accExpenses63->id,
            'period_month'        => 5,
            'allocated_amount'    => 10000.00,
        ]);

        $this->assertEquals(30000.00, (float) $budget->fresh()->current_total_amount);

        // 1. Ampliación (ALLOCATION) de S/ 5,000 en Partida A
        $mod1 = $this->budgetService->applyModification($budget, [
            'type'                       => 'ALLOCATION',
            'destination_budget_line_id' => $lineA->id,
            'amount'                     => 5000.00,
            'justification'              => 'Crédito suplementario para adquisición de insumos adicionales',
        ], $this->adminUser);

        $this->assertEquals('ALLOCATION', $mod1->type);
        $this->assertEquals(25000.00, (float) $lineA->fresh()->current_amount);
        $this->assertEquals(5000.00, (float) $lineA->fresh()->modified_amount);
        $this->assertEquals(35000.00, (float) $budget->fresh()->current_total_amount);

        // 2. Reducción (CANCELLATION) de S/ 2,000 en Partida B
        $mod2 = $this->budgetService->applyModification($budget, [
            'type'                  => 'CANCELLATION',
            'source_budget_line_id' => $lineB->id,
            'amount'                => 2000.00,
            'justification'         => 'Reajuste por menor requerimiento de terceros',
        ], $this->adminUser);

        $this->assertEquals(8000.00, (float) $lineB->fresh()->current_amount);
        $this->assertEquals(-2000.00, (float) $lineB->fresh()->modified_amount);
        $this->assertEquals(33000.00, (float) $budget->fresh()->current_total_amount);

        // 3. Transferencia (TRANSFER) de S/ 4,000 de Partida A hacia Partida B
        $mod3 = $this->budgetService->applyModification($budget, [
            'type'                       => 'TRANSFER',
            'source_budget_line_id'      => $lineA->id,
            'destination_budget_line_id' => $lineB->id,
            'amount'                     => 4000.00,
            'justification'              => 'Transferencia compensatoria entre genéricas de gasto',
        ], $this->adminUser);

        $this->assertEquals(21000.00, (float) $lineA->fresh()->current_amount); // 25000 - 4000
        $this->assertEquals(12000.00, (float) $lineB->fresh()->current_amount); // 8000 + 4000
        $this->assertEquals(33000.00, (float) $budget->fresh()->current_total_amount); // Total institucional inalterado

        // Historial completo
        $this->assertCount(3, $budget->modifications);

        // Intento de transferencia excesiva arroja excepción
        $this->expectException(DomainException::class);
        $this->budgetService->applyModification($budget, [
            'type'                       => 'TRANSFER',
            'source_budget_line_id'      => $lineA->id,
            'destination_budget_line_id' => $lineB->id,
            'amount'                     => 999999.00,
            'justification'              => 'Monto inviable',
        ], $this->adminUser);
    }

    /**
     * Test 3: Criterio de Aceptación: El devengado coincide estrictamente con los asientos contables publicados
     */
    public function test_budget_execution_accrued_strictly_matches_posted_journal_entries(): void
    {
        $budget = $this->budgetService->createBudget(['fiscal_year' => 2025], $this->adminUser);

        $line = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id'    => $this->accExpenses63->id,
            'productive_activity_id' => $this->activityGavilan->id,
            'fund_source_id'         => $this->fundRdr->id,
            'period_month'           => 5,
            'allocated_amount'       => 10000.00,
        ]);

        // 1. Asiento 1 Publicado (POSTED): Gasto de S/ 4,000.00
        $entry1 = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'DIARIO-B6-001',
            'entry_date'           => '2025-05-10',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Servicios de Mantenimiento Gavilán',
            'currency'             => 'PEN',
            'total_debit'          => 4000.00,
            'total_credit'         => 4000.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry1->id,
            'line_number'      => 1,
            'account_id'       => $this->accExpenses63->id,
            'cost_center_id'   => $this->activityGavilan->id,
            'fund_source_id'   => $this->fundRdr->id,
            'debit'            => 4000.00,
            'credit'           => 0.00,
            'glosa'            => 'Gasto de mantenimiento devengado',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry1->id,
            'line_number'      => 2,
            'account_id'       => $this->accPayables->id,
            'debit'            => 0.00,
            'credit'           => 4000.00,
            'glosa'            => 'Cuenta por pagar mantenimiento',
        ]);

        // 2. Asiento 2 Publicado: Nota de crédito / ajuste de S/ 500.00 a favor
        $entry2 = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'DIARIO-B6-002',
            'entry_date'           => '2025-05-15',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Descuento posterior en servicios',
            'currency'             => 'PEN',
            'total_debit'          => 500.00,
            'total_credit'         => 500.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry2->id,
            'line_number'      => 1,
            'account_id'       => $this->accPayables->id,
            'debit'            => 500.00,
            'credit'           => 0.00,
            'glosa'            => 'Reversión parcial cuenta por pagar',
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry2->id,
            'line_number'      => 2,
            'account_id'       => $this->accExpenses63->id,
            'cost_center_id'   => $this->activityGavilan->id,
            'fund_source_id'   => $this->fundRdr->id,
            'debit'            => 0.00,
            'credit'           => 500.00,
            'glosa'            => 'Ajuste crédito gasto 6311',
        ]);

        // 3. Asiento 3 en Borrador (DRAFT): S/ 8,000.00 (NO debe sumarse al devengado)
        $entryDraft = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'DRAFT-B6-003',
            'entry_date'           => '2025-05-20',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Borrador preliminar',
            'currency'             => 'PEN',
            'total_debit'          => 8000.00,
            'total_credit'         => 8000.00,
            'status'               => JournalStatus::DRAFT,
            'created_by_user_id'   => $this->adminUser->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entryDraft->id,
            'line_number'      => 1,
            'account_id'       => $this->accExpenses63->id,
            'cost_center_id'   => $this->activityGavilan->id,
            'fund_source_id'   => $this->fundRdr->id,
            'debit'            => 8000.00,
            'credit'           => 0.00,
            'glosa'            => 'Gasto no publicado',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entryDraft->id,
            'line_number'      => 2,
            'account_id'       => $this->accPayables->id,
            'debit'            => 0.00,
            'credit'           => 8000.00,
            'glosa'            => 'Contrapartida',
        ]);

        // Ejecutar cálculo del devengado
        $exec = $this->budgetService->calculateLineExecution($line);

        // 4000.00 debito - 500.00 credito = exactamente S/ 3,500.00 devengado
        $this->assertEquals(3500.00, $exec['accrued_amount']);
        $this->assertEquals(6500.00, $exec['available_amount']); // 10,000 - 3,500
        $this->assertEquals(35.00, $exec['execution_percentage']); // 35.0%
        $this->assertEquals('GREEN', $exec['traffic_light']);
    }

    /**
     * Test 4: Etapas de Ejecución Presupuestal: Comprometido, Devengado y Girado (Tesorería)
     */
    public function test_budget_execution_stages_committed_accrued_and_paid(): void
    {
        $budget = $this->budgetService->createBudget(['fiscal_year' => 2025], $this->adminUser);

        $line = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id'    => $this->accExpenses60->id,
            'productive_activity_id' => $this->activityGavilan->id,
            'fund_source_id'         => $this->fundRdr->id,
            'period_month'           => 5,
            'allocated_amount'       => 20000.00,
        ]);

        // 1. Requerimiento institucional aprobado por S/ 6,000 (Compromiso)
        Requisition::create([
            'correlativo' => 'REQ-TEST-B6-' . uniqid(),
            'user_id'     => $this->adminUser->id,
            'de'          => 'Jefe de Campo',
            'cargo'       => 'Responsable de Actividad',
            'fecha'       => '2025-05-05',
            'finalidad'   => 'Compra de fertilizantes palma',
            'total'       => 6000.00,
            'status'      => 'APROBADO',
        ]);

        // 2. Devengado contable por S/ 4,000
        $entryDev = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'DIARIO-DEV-' . uniqid(),
            'entry_date'           => '2025-05-12',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Factura por fertilizantes entregados',
            'currency'             => 'PEN',
            'total_debit'          => 4000.00,
            'total_credit'         => 4000.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entryDev->id,
            'line_number'      => 1,
            'account_id'       => $this->accExpenses60->id,
            'cost_center_id'   => $this->activityGavilan->id,
            'fund_source_id'   => $this->fundRdr->id,
            'debit'            => 4000.00,
            'credit'           => 0.00,
            'glosa'            => 'Devengado de fertilizantes',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entryDev->id,
            'line_number'      => 2,
            'account_id'       => $this->accPayables->id,
            'debit'            => 0.00,
            'credit'           => 4000.00,
            'glosa'            => 'Cuenta por pagar proveedor',
        ]);

        // 3. Pago en Tesorería (Girado) por S/ 3,000 mediante débito a cuenta 104x
        $entryPago = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'DIARIO-PAG-' . uniqid(),
            'entry_date'           => '2025-05-20',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Cheque / transferencia a proveedor',
            'currency'             => 'PEN',
            'total_debit'          => 3000.00,
            'total_credit'         => 3000.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entryPago->id,
            'line_number'      => 1,
            'account_id'       => $this->accPayables->id,
            'debit'            => 3000.00,
            'credit'           => 0.00,
            'glosa'            => 'Pago de factura',
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entryPago->id,
            'line_number'      => 2,
            'account_id'       => $this->accCashBank->id,
            'cost_center_id'   => $this->activityGavilan->id,
            'fund_source_id'   => $this->fundRdr->id,
            'debit'            => 0.00,
            'credit'           => 3000.00,
            'glosa'            => 'Desembolso bancario giro',
        ]);

        $exec = $this->budgetService->calculateLineExecution($line);

        $this->assertEquals(20000.00, $exec['current_amount']);
        $this->assertGreaterThanOrEqual(4000.00, $exec['committed_amount']);
        $this->assertEquals(4000.00, $exec['accrued_amount']);
        $this->assertEquals(3000.00, $exec['paid_amount']);
        $this->assertEquals(16000.00, $exec['available_amount']);
    }

    /**
     * Test 5: Semáforo Presupuestal: Normal (Verde), Alerta (Amarillo) y Crítico (Rojo)
     */
    public function test_traffic_light_indicator_system(): void
    {
        $budget = $this->budgetService->createBudget([
            'fiscal_year'                => 2025,
            'alert_threshold_percentage' => 90.00,
        ], $this->adminUser);

        $line = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id' => $this->accExpenses63->id,
            'period_month'        => 5,
            'allocated_amount'    => 10000.00,
        ]);

        // Caso Verde (< 70%): S/ 4,000 devengado
        $entry1 = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'GREEN-' . uniqid(),
            'entry_date'           => '2025-05-02',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Gasto normal verde',
            'currency'             => 'PEN',
            'total_debit'          => 4000.00,
            'total_credit'         => 4000.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry1->id,
            'line_number'      => 1,
            'account_id'       => $this->accExpenses63->id,
            'debit'            => 4000.00,
            'credit'           => 0.00,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry1->id,
            'line_number'      => 2,
            'account_id'       => $this->accPayables->id,
            'debit'            => 0.00,
            'credit'           => 4000.00,
        ]);

        $exec1 = $this->budgetService->calculateLineExecution($line);
        $this->assertEquals(40.00, $exec1['execution_percentage']);
        $this->assertEquals('GREEN', $exec1['traffic_light']);

        // Caso Amarillo (70% - 89%): S/ 3,500 adicionales (Total 7,500 = 75%)
        $entry2 = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'YELLOW-' . uniqid(),
            'entry_date'           => '2025-05-10',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Gasto alerta amarillo',
            'currency'             => 'PEN',
            'total_debit'          => 3500.00,
            'total_credit'         => 3500.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry2->id,
            'line_number'      => 1,
            'account_id'       => $this->accExpenses63->id,
            'debit'            => 3500.00,
            'credit'           => 0.00,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry2->id,
            'line_number'      => 2,
            'account_id'       => $this->accPayables->id,
            'debit'            => 0.00,
            'credit'           => 3500.00,
        ]);

        $exec2 = $this->budgetService->calculateLineExecution($line);
        $this->assertEquals(75.00, $exec2['execution_percentage']);
        $this->assertEquals('YELLOW', $exec2['traffic_light']);

        // Caso Rojo (>= 90%): S/ 1,800 adicionales (Total 9,300 = 93%)
        $entry3 = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'RED-' . uniqid(),
            'entry_date'           => '2025-05-20',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Gasto critico rojo',
            'currency'             => 'PEN',
            'total_debit'          => 1800.00,
            'total_credit'         => 1800.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry3->id,
            'line_number'      => 1,
            'account_id'       => $this->accExpenses63->id,
            'debit'            => 1800.00,
            'credit'           => 0.00,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry3->id,
            'line_number'      => 2,
            'account_id'       => $this->accPayables->id,
            'debit'            => 0.00,
            'credit'           => 1800.00,
        ]);

        $exec3 = $this->budgetService->calculateLineExecution($line);
        $this->assertEquals(93.00, $exec3['execution_percentage']);
        $this->assertEquals('RED', $exec3['traffic_light']);
        $this->assertTrue($exec3['is_exceeded']);
    }

    /**
     * Test 6: Criterio de Aceptación: Superar el umbral genera notificación en BD UNA SOLA VEZ
     */
    public function test_acceptance_threshold_exceeded_triggers_notification_only_once(): void
    {
        $budget = $this->budgetService->createBudget([
            'fiscal_year'                => 2025,
            'alert_threshold_percentage' => 85.00,
        ], $this->adminUser);

        $line = $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id'    => $this->accExpenses65->id,
            'productive_activity_id' => $this->activityGavilan->id,
            'period_month'           => 5,
            'allocated_amount'       => 10000.00,
        ]);

        // Registrar gasto devengado de S/ 9,200 (92% de ejecución, superando el umbral de 85%)
        $entry = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'ALERTA-' . uniqid(),
            'entry_date'           => '2025-05-18',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Gasto superior al umbral presupuestal',
            'currency'             => 'PEN',
            'total_debit'          => 9200.00,
            'total_credit'         => 9200.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'line_number'      => 1,
            'account_id'       => $this->accExpenses65->id,
            'cost_center_id'   => $this->activityGavilan->id,
            'debit'            => 9200.00,
            'credit'           => 0.00,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'line_number'      => 2,
            'account_id'       => $this->accPayables->id,
            'debit'            => 0.00,
            'credit'           => 9200.00,
        ]);

        // Conteo inicial de notificaciones de tipo BudgetThresholdExceededNotification
        $initialNotificationsCount = DB::table('notifications')
            ->where('type', BudgetThresholdExceededNotification::class)
            ->where('data', 'like', '%"budget_line_id":' . $line->id . '%')
            ->count();
        $this->assertEquals(0, $initialNotificationsCount, 'No debe haber notificaciones previas para esta partida.');

        // PRIMERA EJECUCIÓN DEL MONITOR DE ALERTAS
        $firstCheck = $this->budgetService->checkThresholdAlerts($budget);

        $this->assertCount(1, $firstCheck, 'Debe generar exactamente una alerta en el primer chequeo.');
        $this->assertNotNull($line->fresh()->alert_sent_at, 'La partida debe quedar marcada con alert_sent_at.');

        $notificationsAfterFirstCheck = DB::table('notifications')
            ->where('type', BudgetThresholdExceededNotification::class)
            ->where('data', 'like', '%"budget_line_id":' . $line->id . '%')
            ->count();
        $this->assertGreaterThan(0, $notificationsAfterFirstCheck, 'Debe haberse insertado la notificación en la base de datos.');

        // SEGUNDA EJECUCIÓN INMEDIATA DEL MONITOR DE ALERTAS (IDEMPOTENCIA ESTRICTA)
        $secondCheck = $this->budgetService->checkThresholdAlerts($budget);

        $this->assertCount(0, $secondCheck, 'El segundo chequeo NO debe generar nuevas alertas.');

        $notificationsAfterSecondCheck = DB::table('notifications')
            ->where('type', BudgetThresholdExceededNotification::class)
            ->where('data', 'like', '%"budget_line_id":' . $line->id . '%')
            ->count();

        // VALIDACIÓN ESTRICTA: El conteo de notificaciones no cambia
        $this->assertEquals(
            $notificationsAfterFirstCheck,
            $notificationsAfterSecondCheck,
            'Criterio de aceptación: La superación del umbral debe disparar la notificación UNA SOLA VEZ.'
        );

        // TERCERA EJECUCIÓN (Llamada HTTP a través del endpoint)
        $response = $this->actingAs($this->adminUser)->postJson(route('accounting.budgets.check_alerts', $budget->id));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'alerts_created' => 0]);
    }

    /**
     * Test 7: Flujo de Aprobación Oficial vía DocumentApprovalService (Tipo 11: BudgetApproval)
     */
    public function test_document_approval_workflow_type_11_budget_approval(): void
    {
        $budget = $this->budgetService->createBudget([
            'fiscal_year' => 2025,
            'name'        => 'Presupuesto Institucional para Aprobación 2025',
        ], $this->contabilidadUser);

        $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id' => $this->accExpenses60->id,
            'period_month'        => 1,
            'allocated_amount'    => 50000.00,
        ]);

        $this->assertEquals('DRAFT', $budget->status);

        // Enviar a aprobación oficial (Tipo 11)
        $approvalDoc = $this->budgetService->submitForApproval($budget, $this->contabilidadUser);

        $this->assertInstanceOf(BudgetApproval::class, $approvalDoc);
        $this->assertEquals('EN_REVISION', $budget->fresh()->status);

        // Verificar los 3 pasos de aprobación
        $steps = DocumentApproval::where('document_type', BudgetApproval::class)
            ->where('document_id', $approvalDoc->id)
            ->orderBy('step_order')
            ->get();

        $this->assertCount(3, $steps);
        $this->assertEquals('CONTABILIDAD', $steps[0]->role_name);
        $this->assertEquals('ADMINISTRACION', $steps[1]->role_name);
        $this->assertEquals('DIRECTOR_GENERAL', $steps[2]->role_name);

        // Paso 1: Firma Jefe de Contabilidad
        $this->approvalService->signAndApprove($steps[0], $this->contabilidadUser, 'Conformidad de Contabilidad y Presupuesto');
        $this->assertEquals('APROBADO', $steps[0]->fresh()->status);
        $this->assertEquals('PENDIENTE', $steps[1]->fresh()->status);
        $this->assertEquals('EN_REVISION', $budget->fresh()->status);

        // Paso 2: Firma Jefe de Administración
        $this->approvalService->signAndApprove($steps[1], $this->administracionUser, 'Conformidad Administrativa Institucional');
        $this->assertEquals('APROBADO', $steps[1]->fresh()->status);
        $this->assertEquals('PENDIENTE', $steps[2]->fresh()->status);
        $this->assertEquals('EN_REVISION', $budget->fresh()->status);

        // Paso 3: Firma Director General (Aprobación Final)
        $this->approvalService->signAndApprove($steps[2], $this->directorGeneralUser, 'Resolución Directoral de Aprobación');
        $this->assertEquals('APROBADO', $steps[2]->fresh()->status);

        // Al completarse el paso 3, el presupuesto transiciona a APPROVED
        $this->assertEquals('APPROVED', $budget->fresh()->status);
        $this->assertNotNull($budget->fresh()->approved_at);

        // Activar presupuesto aprobado
        $this->budgetService->activateBudget($budget);
        $this->assertEquals('ACTIVE', $budget->fresh()->status);
        $this->assertNotNull($budget->fresh()->activated_at);
    }

    /**
     * Test 8: Integración con Centros de Costo (Matriz Actividad x Mes sin duplicar datos)
     */
    public function test_integration_with_cost_centers_activity_x_month_matrix_without_duplication(): void
    {
        $budget = $this->budgetService->createBudget(['fiscal_year' => 2025, 'status' => 'ACTIVE'], $this->adminUser);

        // Partidas presupuestadas para Gavilán en Mes 5 y Mes 6
        $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id'    => $this->accExpenses60->id,
            'productive_activity_id' => $this->activityGavilan->id,
            'period_month'           => 5,
            'allocated_amount'       => 12000.00,
        ]);

        $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id'    => $this->accExpenses63->id,
            'productive_activity_id' => $this->activityGavilan->id,
            'period_month'           => 6,
            'allocated_amount'       => 18000.00,
        ]);

        // Registrar asiento contable real con cost_center_id de Gavilán en Mes 5
        $entry = JournalEntry::create([
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'CC-ASIENTO-' . uniqid(),
            'entry_date'           => '2025-05-14',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Costo operacional Fundo Gavilán',
            'currency'             => 'PEN',
            'total_debit'          => 7500.00,
            'total_credit'         => 7500.00,
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'line_number'      => 1,
            'account_id'       => $this->accExpenses60->id,
            'cost_center_id'   => $this->activityGavilan->id,
            'debit'            => 7500.00,
            'credit'           => 0.00,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'line_number'      => 2,
            'account_id'       => $this->accPayables->id,
            'debit'            => 0.00,
            'credit'           => 7500.00,
        ]);

        // Generar la matriz consolidada Actividad x Mes
        $matrix = $this->budgetService->getCostCenterExecutionMatrix(2025);

        $this->assertEquals(2025, $matrix['fiscal_year']);
        $this->assertNotEmpty($matrix['activities']);

        $gavilanRow = collect($matrix['activities'])->firstWhere('activity_id', $this->activityGavilan->id);
        $this->assertNotNull($gavilanRow);

        // Mes 5: Presupuestado 12,000 | Ejecutado (Devengado de asiento) = 7,500
        $month5 = $gavilanRow['months'][5];
        $this->assertEquals(12000.00, $month5['budgeted']);
        $this->assertEquals(7500.00, $month5['executed']);
        $this->assertEquals(4500.00, $month5['variance']);
        $this->assertEquals(62.50, $month5['execution_percentage']);
        $this->assertEquals('GREEN', $month5['traffic_light']);

        // Mes 6: Presupuestado 18,000 | Ejecutado = 0 (Aún no hay asientos para mes 6)
        $month6 = $gavilanRow['months'][6];
        $this->assertEquals(18000.00, $month6['budgeted']);
        $this->assertEquals(0.00, $month6['executed']);

        // Totales consolidados de la actividad Gavilán
        $this->assertEquals(30000.00, $gavilanRow['annual_budgeted']);
        $this->assertEquals(7500.00, $gavilanRow['annual_executed']);
    }

    /**
     * Test 9: Rutas Web, Tablero KPI y Exportaciones (Excel / PDF)
     */
    public function test_budget_web_routes_kpi_dashboard_and_pdf_excel_exports(): void
    {
        $budget = $this->budgetService->createBudget(['fiscal_year' => 2025], $this->adminUser);

        $this->budgetService->addBudgetLine($budget, [
            'chart_of_account_id' => $this->accExpenses60->id,
            'period_month'        => 5,
            'allocated_amount'    => 10000.00,
        ]);

        // 1. Listado index
        $resIndex = $this->actingAs($this->adminUser)->get(route('accounting.budgets.index', ['year' => 2025]));
        $resIndex->assertStatus(200);

        // 2. Detalle show
        $resShow = $this->actingAs($this->adminUser)->get(route('accounting.budgets.show', $budget->id));
        $resShow->assertStatus(200);
        $resShow->assertSee($budget->code);

        // 3. Tablero KPI
        $resKpi = $this->actingAs($this->adminUser)->get(route('accounting.budgets.kpi_dashboard', $budget->id));
        $resKpi->assertStatus(200);

        // 4. Agregar línea vía endpoint
        $resLine = $this->actingAs($this->adminUser)->postJson(route('accounting.budgets.lines.store', $budget->id), [
            'period_month'     => 7,
            'allocated_amount' => 5000.00,
            'category_name'    => 'Combustible y Lubricantes',
        ]);
        $resLine->assertStatus(200);
        $resLine->assertJson(['success' => true]);

        // 5. Modificación presupuestal vía endpoint
        $lineId = $budget->fresh()->lines->first()->id;
        $resMod = $this->actingAs($this->adminUser)->postJson(route('accounting.budgets.modifications.store', $budget->id), [
            'type'                       => 'ALLOCATION',
            'destination_budget_line_id' => $lineId,
            'amount'                     => 1500.00,
            'justification'              => 'Refuerzo presupuestal partida combustible',
        ]);
        $resMod->assertStatus(200);
        $resMod->assertJson(['success' => true]);

        // 6. Exportación a Excel
        $resExcel = $this->actingAs($this->adminUser)->get(route('accounting.budgets.export_excel', $budget->id));
        $resExcel->assertStatus(200);
        $this->assertTrue($resExcel->headers->get('content-disposition') !== null || $resExcel->getStatusCode() === 200);

        // 7. Exportación a PDF
        $resPdf = $this->actingAs($this->adminUser)->get(route('accounting.budgets.export_pdf', $budget->id));
        $resPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $resPdf->headers->get('content-type'));
    }
}
