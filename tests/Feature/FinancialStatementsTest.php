<?php

namespace Tests\Feature;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\AccountingPeriodAuditLog;
use App\Models\AccountingPeriodClosure;
use App\Models\ChartOfAccount;
use App\Models\FinancialStatementTemplate;
use App\Models\FinancialStatementTemplateLine;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ProductiveActivity;
use App\Models\User;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Accounting\PeriodClosingService;
use App\Services\DocumentApprovalService;
use Database\Seeders\FinancialStatementTemplateSeeder;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialStatementsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $contabilidadUser;
    protected User $administracionUser;
    protected User $directorGeneralUser;

    protected FinancialStatementService $statementService;
    protected PeriodClosingService $closingService;
    protected DocumentApprovalService $approvalService;

    protected AccountingPeriod $periodPrev;
    protected AccountingPeriod $periodCurrent;

    // Accounts
    protected ChartOfAccount $accCash;
    protected ChartOfAccount $accBank;
    protected ChartOfAccount $accReceivables;
    protected ChartOfAccount $accInventory;
    protected ChartOfAccount $accVehicles;
    protected ChartOfAccount $accDepreciation;
    protected ChartOfAccount $accPayables;
    protected ChartOfAccount $accLoans;
    protected ChartOfAccount $accCapital;
    protected ChartOfAccount $accRetainedEarnings;
    protected ChartOfAccount $accSales;
    protected ChartOfAccount $accCostOfSales;
    protected ChartOfAccount $accAdminExpenses;
    protected ChartOfAccount $accSellingExpenses;
    protected ChartOfAccount $accFinancialExpenses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statementService = app(FinancialStatementService::class);
        $this->closingService   = app(PeriodClosingService::class);
        $this->approvalService  = app(DocumentApprovalService::class);

        // Ensure Templates are seeded
        $this->seed(FinancialStatementTemplateSeeder::class);

        // Ensure Permissions
        $viewPerm   = Permission::firstOrCreate(['name' => 'accounting.view'], ['descripcion' => 'Ver contabilidad']);
        $exportPerm = Permission::firstOrCreate(['name' => 'accounting.export'], ['descripcion' => 'Exportar contabilidad']);

        // Roles
        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleAdmin->givePermissionTo([$viewPerm, $exportPerm]);

        $roleCont = Role::firstOrCreate(['name' => 'CONTABILIDAD']);
        $roleCont->givePermissionTo([$viewPerm, $exportPerm]);

        $roleAdm = Role::firstOrCreate(['name' => 'ADMINISTRACION']);
        $roleAdm->givePermissionTo([$viewPerm, $exportPerm]);

        $roleDg = Role::firstOrCreate(['name' => 'DIRECTOR_GENERAL']);
        $roleDg->givePermissionTo([$viewPerm, $exportPerm]);

        // Users
        $this->adminUser = User::firstOrCreate(
            ['user' => 'admin_stmt_test'],
            [
                'nombres'   => 'Admin Statement Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->adminUser->syncRoles(['ADMIN']);
        $this->adminUser->givePermissionTo([$viewPerm, $exportPerm]);

        $this->contabilidadUser = User::firstOrCreate(
            ['user' => 'cont_stmt_test'],
            [
                'nombres'   => 'Jefe Contabilidad Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->contabilidadUser->syncRoles(['CONTABILIDAD']);

        $this->administracionUser = User::firstOrCreate(
            ['user' => 'adm_stmt_test'],
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
            ['user' => 'dg_stmt_test'],
            [
                'nombres'   => 'Director General Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->directorGeneralUser->syncRoles(['DIRECTOR_GENERAL']);

        // Setup Periods
        $this->periodPrev = AccountingPeriod::firstOrCreate(
            ['period_code' => '2026-09'],
            [
                'fiscal_year' => 2026,
                'month'       => 9,
                'start_date'  => '2026-09-01',
                'end_date'    => '2026-09-30',
                'status'      => 'OPEN',
            ]
        );

        $this->periodCurrent = AccountingPeriod::firstOrCreate(
            ['period_code' => '2026-10'],
            [
                'fiscal_year' => 2026,
                'month'       => 10,
                'start_date'  => '2026-10-01',
                'end_date'    => '2026-10-31',
                'status'      => 'OPEN',
            ]
        );

        // Chart of Accounts (standard PCGE)
        $this->accCash = ChartOfAccount::firstOrCreate(
            ['code' => '1011'],
            ['name' => 'Caja Central', 'element' => 1, 'account_type' => AccountType::ACTIVO, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accBank = ChartOfAccount::firstOrCreate(
            ['code' => '1041'],
            ['name' => 'Cuentas Corrientes Operativas', 'element' => 1, 'account_type' => AccountType::ACTIVO, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accReceivables = ChartOfAccount::firstOrCreate(
            ['code' => '1212'],
            ['name' => 'Facturas por Cobrar Comerciales', 'element' => 1, 'account_type' => AccountType::ACTIVO, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accInventory = ChartOfAccount::firstOrCreate(
            ['code' => '20111'],
            ['name' => 'Mercaderías Manufacturadas', 'element' => 2, 'account_type' => AccountType::ACTIVO, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accVehicles = ChartOfAccount::firstOrCreate(
            ['code' => '3341'],
            ['name' => 'Vehículos Motorizados', 'element' => 3, 'account_type' => AccountType::ACTIVO, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accDepreciation = ChartOfAccount::firstOrCreate(
            ['code' => '3913'],
            ['name' => 'Depreciación Acumulada - Vehículos', 'element' => 3, 'account_type' => AccountType::ACTIVO, 'nature' => AccountNature::CREDIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accPayables = ChartOfAccount::firstOrCreate(
            ['code' => '4212'],
            ['name' => 'Facturas por Pagar Comerciales', 'element' => 4, 'account_type' => AccountType::PASIVO, 'nature' => AccountNature::CREDIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accLoans = ChartOfAccount::firstOrCreate(
            ['code' => '4511'],
            ['name' => 'Préstamos Bancarios Corrientes', 'element' => 4, 'account_type' => AccountType::PASIVO, 'nature' => AccountNature::CREDIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accCapital = ChartOfAccount::firstOrCreate(
            ['code' => '5011'],
            ['name' => 'Capital Social Suscrito y Pagado', 'element' => 5, 'account_type' => AccountType::PATRIMONIO, 'nature' => AccountNature::CREDIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accRetainedEarnings = ChartOfAccount::firstOrCreate(
            ['code' => '5911'],
            ['name' => 'Utilidades Acumuladas', 'element' => 5, 'account_type' => AccountType::PATRIMONIO, 'nature' => AccountNature::CREDIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accSales = ChartOfAccount::firstOrCreate(
            ['code' => '7011'],
            ['name' => 'Ventas de Mercaderías', 'element' => 7, 'account_type' => AccountType::INGRESOS, 'nature' => AccountNature::CREDIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accCostOfSales = ChartOfAccount::firstOrCreate(
            ['code' => '6911'],
            ['name' => 'Costo de Ventas - Mercaderías', 'element' => 6, 'account_type' => AccountType::GASTOS_NATURALEZA, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accAdminExpenses = ChartOfAccount::firstOrCreate(
            ['code' => '9411'],
            ['name' => 'Gastos Administrativos', 'element' => 9, 'account_type' => AccountType::GASTOS_FUNCION, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accSellingExpenses = ChartOfAccount::firstOrCreate(
            ['code' => '9511'],
            ['name' => 'Gastos de Ventas', 'element' => 9, 'account_type' => AccountType::GASTOS_FUNCION, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );
        $this->accFinancialExpenses = ChartOfAccount::firstOrCreate(
            ['code' => '6711'],
            ['name' => 'Gastos Financieros - Intereses', 'element' => 6, 'account_type' => AccountType::GASTOS_NATURALEZA, 'nature' => AccountNature::DEBIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );

    }

    /**
     * Helper to create a posted balanced journal entry.
     */
    protected function createPostedEntry(AccountingPeriod $period, string $date, string $concept, array $lines): JournalEntry
    {
        $totalDebit  = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        $entry = JournalEntry::create([
            'entry_number'        => 'TEST-' . uniqid(),
            'accounting_period_id' => $period->id,
            'entry_date'           => $date,
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => $concept,
            'total_debit'          => round($totalDebit, 2),
            'total_credit'         => round($totalCredit, 2),
            'difference'          => round(abs($totalDebit - $totalCredit), 2),
            'status'              => JournalStatus::POSTED,
            'created_by_user_id'  => $this->adminUser->id,
            'posted_at'           => now(),
        ]);

        foreach ($lines as $idx => $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'line_number'      => $idx + 1,
                'account_id'       => $line['account_id'],
                'cost_center_id'   => $line['cost_center_id'] ?? null,
                'debit'            => round($line['debit'], 2),
                'credit'           => round($line['credit'], 2),
                'glosa'            => $line['glosa'] ?? $concept,
            ]);
        }

        return $entry;
    }

    /**
     * Test 1: Verify templates and line configurations exist.
     */
    public function test_financial_statement_templates_seeded_and_have_lines(): void
    {
        $balanceTemplate = FinancialStatementTemplate::where('code', 'BALANCE_SHEET')->first();
        $this->assertNotNull($balanceTemplate);
        $this->assertGreaterThan(10, $balanceTemplate->lines()->count());

        $natureTemplate = FinancialStatementTemplate::where('code', 'INCOME_STATEMENT_NATURE')->first();
        $this->assertNotNull($natureTemplate);
        $this->assertGreaterThan(5, $natureTemplate->lines()->count());

        $functionTemplate = FinancialStatementTemplate::where('code', 'INCOME_STATEMENT_FUNCTION')->first();
        $this->assertNotNull($functionTemplate);
        $this->assertGreaterThan(5, $functionTemplate->lines()->count());
    }

    /**
     * Test 2: Balance Sheet calculates Assets, Liabilities, and Equity from posted lines.
     */
    public function test_balance_sheet_calculates_assets_liabilities_and_equity(): void
    {
        // Balanced Opening / Operating entries in 2026-10:
        // Cash: 5,000 (Dr)
        // Bank: 15,000 (Dr)
        // Receivables: 10,000 (Dr)
        // Inventory: 20,000 (Dr)
        // Vehicles: 50,000 (Dr)
        // Acc Depr: 10,000 (Cr)
        // Payables: 15,000 (Cr)
        // Loan: 25,000 (Cr)
        // Capital: 30,000 (Cr)
        // Retained Earnings: 5,000 (Cr)
        // Sales: 25,000 (Cr)
        // Cost of Sales: 10,000 (Dr)
        // Total Dr: 5k+15k+10k+20k+50k+10k = 110,000
        // Total Cr: 10k+15k+25k+30k+5k+25k = 110,000 (Balanced!)

        $this->createPostedEntry($this->periodCurrent, '2026-10-15', 'Asiento de prueba para Balance General', [
            ['account_id' => $this->accCash->id, 'debit' => 5000, 'credit' => 0],
            ['account_id' => $this->accBank->id, 'debit' => 15000, 'credit' => 0],
            ['account_id' => $this->accReceivables->id, 'debit' => 10000, 'credit' => 0],
            ['account_id' => $this->accInventory->id, 'debit' => 20000, 'credit' => 0],
            ['account_id' => $this->accVehicles->id, 'debit' => 50000, 'credit' => 0],
            ['account_id' => $this->accDepreciation->id, 'debit' => 0, 'credit' => 10000],
            ['account_id' => $this->accPayables->id, 'debit' => 0, 'credit' => 15000],
            ['account_id' => $this->accLoans->id, 'debit' => 0, 'credit' => 25000],
            ['account_id' => $this->accCapital->id, 'debit' => 0, 'credit' => 30000],
            ['account_id' => $this->accRetainedEarnings->id, 'debit' => 0, 'credit' => 5000],
            ['account_id' => $this->accSales->id, 'debit' => 0, 'credit' => 25000],
            ['account_id' => $this->accCostOfSales->id, 'debit' => 10000, 'credit' => 0],
        ]);

        $report = $this->statementService->getBalanceSheet(['period_id' => $this->periodCurrent->id]);

        $this->assertEquals(90000.00, $report['totals']['total_assets']);
        $this->assertEquals(40000.00, $report['totals']['total_liabilities']);
        // Net Result: 25,000 (Sales) - 10,000 (Cost) = 15,000
        $this->assertEquals(15000.00, $report['net_result']);
        // Equity: 30,000 (Capital) + 5,000 (Retained) + 15,000 (Net Result) = 50,000
        $this->assertEquals(50000.00, $report['totals']['total_equity']);
        // Liabilities + Equity: 40,000 + 50,000 = 90,000
        $this->assertEquals(90000.00, $report['totals']['total_liabilities_and_equity']);
    }

    /**
     * Test 3: Acceptance Criterion - Assets = Liabilities + Equity in test dataset.
     */
    public function test_acceptance_criterion_assets_equals_liabilities_plus_equity(): void
    {
        $this->createPostedEntry($this->periodCurrent, '2026-10-10', 'Operaciones comerciales de octubre', [
            ['account_id' => $this->accBank->id, 'debit' => 20000, 'credit' => 0],
            ['account_id' => $this->accReceivables->id, 'debit' => 8000, 'credit' => 0],
            ['account_id' => $this->accInventory->id, 'debit' => 12000, 'credit' => 0],
            ['account_id' => $this->accVehicles->id, 'debit' => 30000, 'credit' => 0],
            ['account_id' => $this->accPayables->id, 'debit' => 0, 'credit' => 10000],
            ['account_id' => $this->accCapital->id, 'debit' => 0, 'credit' => 45000],
            ['account_id' => $this->accSales->id, 'debit' => 0, 'credit' => 25000],
            ['account_id' => $this->accCostOfSales->id, 'debit' => 10000, 'credit' => 0],
        ]);

        $report = $this->statementService->getBalanceSheet(['period_id' => $this->periodCurrent->id]);

        $this->assertTrue($report['totals']['is_balanced'], 'El Balance General debe estar cuadrado.');
        $this->assertEquals(0.00, $report['totals']['imbalance'], 'La diferencia de descuadre debe ser cero.');
        $this->assertNull($report['totals']['alert'], 'No debe emitirse alerta cuando el balance está cuadrado.');
        $this->assertEquals(
            $report['totals']['total_assets'],
            $report['totals']['total_liabilities_and_equity'],
            'Activo debe ser estrictamente idéntico a Pasivo + Patrimonio.'
        );
    }

    /**
     * Test 4: Acceptance Criterion - Income Statement result strictly matches the result on Balance Sheet.
     */
    public function test_acceptance_criterion_income_statement_result_strictly_matches_balance_sheet_result(): void
    {
        // Post sales, cost of sales, and administrative expenses
        $this->createPostedEntry($this->periodCurrent, '2026-10-20', 'Asiento de Ingresos y Gastos', [
            ['account_id' => $this->accReceivables->id, 'debit' => 50000, 'credit' => 0],
            ['account_id' => $this->accSales->id, 'debit' => 0, 'credit' => 50000],
            ['account_id' => $this->accCostOfSales->id, 'debit' => 22000, 'credit' => 0],
            ['account_id' => $this->accInventory->id, 'debit' => 0, 'credit' => 22000],
            ['account_id' => $this->accAdminExpenses->id, 'debit' => 8000, 'credit' => 0],
            ['account_id' => $this->accCash->id, 'debit' => 0, 'credit' => 8000],
        ]);

        $balanceSheet   = $this->statementService->getBalanceSheet(['period_id' => $this->periodCurrent->id]);
        $statementNat   = $this->statementService->getIncomeStatementByNature(['period_id' => $this->periodCurrent->id]);
        $statementFunc  = $this->statementService->getIncomeStatementByFunction(['period_id' => $this->periodCurrent->id]);

        $expectedNetResult = 50000 - 22000 - 8000; // 20,000

        $this->assertEquals($expectedNetResult, $balanceSheet['net_result']);
        $this->assertEquals($expectedNetResult, $statementNat['totals']['resultado_neto']);
        $this->assertEquals($expectedNetResult, $statementFunc['totals']['resultado_neto']);

        // Assert strict equality across all three statements
        $this->assertEquals($statementNat['totals']['resultado_neto'], $balanceSheet['net_result']);
        $this->assertEquals($statementFunc['totals']['resultado_neto'], $balanceSheet['net_result']);
        $this->assertEquals($statementNat['totals']['resultado_neto'], $statementFunc['totals']['resultado_neto']);
    }

    /**
     * Test 5: Balance Sheet alert triggered when there is an imbalance.
     */
    public function test_balance_sheet_triggers_alert_when_imbalanced(): void
    {
        // Use an Element 0 (Cuentas de Orden) account which satisfies journal entry debit=credit,
        // but is excluded from the Balance Sheet (which only includes Elements 1-5 and results).
        $accOrden = ChartOfAccount::firstOrCreate(
            ['code' => '0211'],
            ['name' => 'Cuentas de Orden Acreedoras', 'element' => 0, 'account_type' => AccountType::ORDEN, 'nature' => AccountNature::CREDIT, 'level' => 3, 'allows_movement' => true, 'is_active' => true]
        );

        $this->createPostedEntry($this->periodCurrent, '2026-10-25', 'Asiento con cuenta de orden generando descuadre de balance', [
            ['account_id' => $this->accCash->id, 'debit' => 1000.00, 'credit' => 0.00],
            ['account_id' => $accOrden->id, 'debit' => 0.00, 'credit' => 1000.00],
        ]);

        $report = $this->statementService->getBalanceSheet(['period_id' => $this->periodCurrent->id]);

        $this->assertFalse($report['totals']['is_balanced']);
        $this->assertNotEquals(0.00, $report['totals']['imbalance']);
        $this->assertNotNull($report['totals']['alert']);
        $this->assertStringContainsString('ALERTA: Descuadre contable detectado', $report['totals']['alert']);
    }


    /**
     * Test 6: Income Statement by Nature calculates PCGE intermediate margins correctly.
     */
    public function test_income_statement_by_nature_intermediate_margins(): void
    {
        $this->createPostedEntry($this->periodCurrent, '2026-10-18', 'Transacciones de naturaleza', [
            ['account_id' => $this->accBank->id, 'debit' => 100000, 'credit' => 0],
            ['account_id' => $this->accSales->id, 'debit' => 0, 'credit' => 100000], // Ventas: 100k
            ['account_id' => $this->accCostOfSales->id, 'debit' => 40000, 'credit' => 0], // Compras/Consumo: 40k
            ['account_id' => $this->accInventory->id, 'debit' => 0, 'credit' => 40000],
            ['account_id' => $this->accFinancialExpenses->id, 'debit' => 5000, 'credit' => 0], // Gastos financieros: 5k
            ['account_id' => $this->accPayables->id, 'debit' => 0, 'credit' => 5000],
        ]);

        $report = $this->statementService->getIncomeStatementByNature(['period_id' => $this->periodCurrent->id]);

        $this->assertArrayHasKey('margen_comercial', $report['totals']);
        $this->assertArrayHasKey('resultado_operativo', $report['totals']);
        $this->assertArrayHasKey('resultado_neto', $report['totals']);

        $this->assertEquals(60000.00, $report['totals']['margen_comercial']); // 100k - 40k
        $this->assertEquals(55000.00, $report['totals']['resultado_neto']);     // 60k - 5k fin
    }

    /**
     * Test 7: Income Statement by Function calculates Gross and Operating Profit.
     */
    public function test_income_statement_by_function_intermediate_margins(): void
    {
        $this->createPostedEntry($this->periodCurrent, '2026-10-18', 'Transacciones de funcion', [
            ['account_id' => $this->accCash->id, 'debit' => 80000, 'credit' => 0],
            ['account_id' => $this->accSales->id, 'debit' => 0, 'credit' => 80000], // Ventas: 80k
            ['account_id' => $this->accCostOfSales->id, 'debit' => 30000, 'credit' => 0], // Costo Ventas: 30k
            ['account_id' => $this->accInventory->id, 'debit' => 0, 'credit' => 30000],
            ['account_id' => $this->accAdminExpenses->id, 'debit' => 12000, 'credit' => 0], // Gastos Adm: 12k
            ['account_id' => $this->accSellingExpenses->id, 'debit' => 8000, 'credit' => 0], // Gastos Ventas: 8k
            ['account_id' => $this->accPayables->id, 'debit' => 0, 'credit' => 20000],
        ]);

        $report = $this->statementService->getIncomeStatementByFunction(['period_id' => $this->periodCurrent->id]);

        // Utilidad Bruta: 80k - 30k = 50k
        $this->assertEquals(50000.00, $report['totals']['utilidad_bruta']);
        // Utilidad Operativa: 50k - 12k - 8k = 30k
        $this->assertEquals(30000.00, $report['totals']['utilidad_operativa']);
        // Resultado Neto: 30k
        $this->assertEquals(30000.00, $report['totals']['resultado_neto']);
    }

    /**
     * Test 8: Comparative statement calculates absolute and percentage variances.
     */
    public function test_comparative_statement_calculates_absolute_and_percentage_variance(): void
    {
        // Prior period (September): Sales = 40,000
        $this->createPostedEntry($this->periodPrev, '2026-09-15', 'Ventas de Septiembre', [
            ['account_id' => $this->accBank->id, 'debit' => 40000, 'credit' => 0],
            ['account_id' => $this->accSales->id, 'debit' => 0, 'credit' => 40000],
        ]);

        // Current period (October): Sales = 60,000
        $this->createPostedEntry($this->periodCurrent, '2026-10-15', 'Ventas de Octubre', [
            ['account_id' => $this->accBank->id, 'debit' => 60000, 'credit' => 0],
            ['account_id' => $this->accSales->id, 'debit' => 0, 'credit' => 60000],
        ]);

        $filters = ['period_id' => $this->periodCurrent->id];
        $compFilters = ['period_id' => $this->periodPrev->id];

        $report = $this->statementService->getIncomeStatementByFunction($filters, $compFilters);

        $this->assertTrue($report['has_comparison']);
        $this->assertNotNull($report['comparison']);

        // Check Sales line variance
        $salesLine = collect($report['lines'])->firstWhere('line_code', 'FUNC_VENTAS');
        $this->assertNotNull($salesLine);
        $this->assertEquals(60000.00, $salesLine['current_amount']);
        $this->assertEquals(40000.00, $salesLine['comparison_amount']);
        // Var Abs: 60,000 - 40,000 = +20,000
        $this->assertEquals(20000.00, $salesLine['variance_abs']);
        // Var Pct: (+20,000 / 40,000) * 100 = +50.00%
        $this->assertEquals(50.00, $salesLine['variance_pct']);
    }

    /**
     * Test 9: Monthly closing locks period and records immutable audit trail.
     */
    public function test_monthly_closing_locks_period_and_logs_audit_trail(): void
    {
        $this->assertEquals('OPEN', $this->periodPrev->status);
        $this->assertTrue($this->periodPrev->canPost());

        $closed = $this->closingService->closeMonthlyPeriod($this->periodPrev, $this->adminUser->id, 'Cierre mensual ordinario septiembre');

        $this->assertEquals('CLOSED', $closed->status);
        $this->assertFalse($closed->canPost());
        $this->assertNotNull($closed->closed_at);
        $this->assertEquals($this->adminUser->id, $closed->closed_by_user_id);

        // Verify audit trail
        $audit = AccountingPeriodAuditLog::where('accounting_period_id', $this->periodPrev->id)
            ->where('action', 'CLOSE')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('OPEN', $audit->from_status);
        $this->assertEquals('CLOSED', $audit->to_status);
        $this->assertEquals($this->adminUser->id, $audit->user_id);
    }

    /**
     * Test 10: Audited reopening requires mandatory reason and transitions back to OPEN.
     */
    public function test_audited_reopening_requires_reason_and_transitions_to_open(): void
    {
        // First close
        $this->closingService->closeMonthlyPeriod($this->periodPrev, $this->adminUser->id);
        $this->assertEquals('CLOSED', $this->periodPrev->fresh()->status);

        // Attempt reopening without reason must throw exception
        $this->expectException(DomainException::class);
        $this->closingService->reopenPeriod($this->periodPrev, $this->adminUser->id, '   ');
    }

    /**
     * Test 10b: Audited reopening with valid reason successfully reopens period.
     */
    public function test_audited_reopening_with_valid_reason_succeeds(): void
    {
        $this->closingService->closeMonthlyPeriod($this->periodPrev, $this->adminUser->id);
        $this->assertEquals('CLOSED', $this->periodPrev->fresh()->status);

        $reason = 'Reapertura para registro de provisión de servicios públicos omitida';
        $reopened = $this->closingService->reopenPeriod($this->periodPrev, $this->adminUser->id, $reason);

        $this->assertEquals('OPEN', $reopened->status);
        $this->assertTrue($reopened->canPost());
        $this->assertNotNull($reopened->reopened_at);
        $this->assertEquals($this->adminUser->id, $reopened->reopened_by_user_id);

        // Verify audit log
        $audit = AccountingPeriodAuditLog::where('accounting_period_id', $this->periodPrev->id)
            ->where('action', 'REOPEN')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('CLOSED', $audit->from_status);
        $this->assertEquals('OPEN', $audit->to_status);
        $this->assertEquals($reason, $audit->reason);
    }

    /**
     * Test 11: Annual closing generates closing journal entries and opening entry for fiscal year+1.
     */
    public function test_annual_closing_generates_closing_and_opening_entries(): void
    {
        // Create period December 2026
        $periodDec = AccountingPeriod::firstOrCreate(
            ['period_code' => '2026-12'],
            [
                'fiscal_year' => 2026,
                'month'       => 12,
                'start_date'  => '2026-12-01',
                'end_date'    => '2026-12-31',
                'status'      => 'OPEN',
            ]
        );

        // Post entries in 2026:
        // Cash: 20,000 (Dr)
        // Capital: 10,000 (Cr)
        // Sales: 30,000 (Cr)
        // Cost: 20,000 (Dr)
        $this->createPostedEntry($periodDec, '2026-12-30', 'Operaciones de fin de año', [
            ['account_id' => $this->accCash->id, 'debit' => 20000, 'credit' => 0],
            ['account_id' => $this->accCapital->id, 'debit' => 0, 'credit' => 10000],
            ['account_id' => $this->accSales->id, 'debit' => 0, 'credit' => 30000],
            ['account_id' => $this->accCostOfSales->id, 'debit' => 20000, 'credit' => 0],
        ]);

        $result = $this->closingService->closeAnnualPeriod($periodDec, $this->adminUser->id, 'Cierre anual del ejercicio 2026');

        $this->assertEquals(10000.00, $result['net_result']); // 30k sales - 20k cost = 10k
        $this->assertEquals('LOCKED', $result['period']->status);
        $this->assertNotNull($result['closing_results_entry']);
        $this->assertNotNull($result['closing_balance_entry']);
        $this->assertNotNull($result['opening_entry']);

        // Check closing entry 1 (VoucherType::CLOSING)
        $this->assertEquals(VoucherType::CLOSING, $result['closing_results_entry']->entry_type);

        // Check opening entry for 2027 (VoucherType::OPENING)
        $this->assertEquals(VoucherType::OPENING, $result['opening_entry']->entry_type);
        $this->assertEquals('2027-01-01', $result['opening_entry']->entry_date->toDateString());


        // Check next period 2027-01 was created
        $period2027 = AccountingPeriod::where('period_code', '2027-01')->first();
        $this->assertNotNull($period2027);
        $this->assertEquals('OPEN', $period2027->status);
    }

    /**
     * Test 12: DocumentApprovalService Type 10 workflow: Accounting -> Administration -> General Management.
     */
    public function test_document_approval_workflow_type_10_accounting_period_closure(): void
    {
        // 1. Submit period for closure approval
        $closure = $this->closingService->submitClosureForApproval(
            $this->periodCurrent,
            'MONTHLY',
            $this->contabilidadUser,
            'Solicitud de cierre mensual de octubre'
        );

        $this->assertNotNull($closure);
        $this->assertEquals('SOFT_CLOSED', $this->periodCurrent->fresh()->status);
        $this->assertEquals('PENDIENTE', $closure->status);

        // Check approvals generated
        $approvals = $closure->approvals()->get();
        $this->assertCount(3, $approvals);

        $stepContabilidad = $approvals[0];
        $stepAdministracion = $approvals[1];
        $stepDirectorGeneral = $approvals[2];

        $this->assertEquals('CONTABILIDAD', $stepContabilidad->role_name);
        $this->assertEquals('ADMINISTRACION', $stepAdministracion->role_name);
        $this->assertEquals('DIRECTOR_GENERAL', $stepDirectorGeneral->role_name);

        // Step 1: Sign by Contabilidad
        $this->approvalService->signAndApprove($stepContabilidad, $this->contabilidadUser, null, 'Conforme con el balance de comprobación');
        $this->assertEquals('APROBADO', $stepContabilidad->fresh()->status);
        $this->assertEquals('EN_REVISION', $closure->fresh()->status);

        // Step 2: Sign by Administración
        $this->approvalService->signAndApprove($stepAdministracion, $this->administracionUser, null, 'Conformidad presupuestal y financiera');
        $this->assertEquals('APROBADO', $stepAdministracion->fresh()->status);
        $this->assertEquals('EN_REVISION', $closure->fresh()->status);

        // Step 3: Sign by Dirección General (Final step)
        $this->approvalService->signAndApprove($stepDirectorGeneral, $this->directorGeneralUser, null, 'Aprobación final del período contable');
        $this->assertEquals('APROBADO', $stepDirectorGeneral->fresh()->status);

        // Document should be APPROVED and period finalized to CLOSED
        $closure->refresh();
        $this->assertEquals('APPROVED', $closure->status);
        $this->assertEquals('CLOSED', $this->periodCurrent->fresh()->status);
    }

    /**
     * Test 13: Artisan commands accounting:close-period and accounting:reopen-period.
     */
    public function test_artisan_commands_accounting_close_period_and_reopen_period(): void
    {
        // 1. Run close-period command
        $this->artisan('accounting:close-period', [
            'period_code' => '2026-09',
            '--notes'     => 'Cierre mensual vía comando Artisan',
        ])
        ->expectsOutputToContain('cerrado con éxito')
        ->assertSuccessful();

        $this->assertEquals('CLOSED', $this->periodPrev->fresh()->status);

        // 2. Run reopen-period command
        $this->artisan('accounting:reopen-period', [
            'period_code' => '2026-09',
            '--reason'    => 'Reapertura para auditoría de prueba',
        ])
        ->expectsOutputToContain('reabierto con éxito')
        ->assertSuccessful();

        $this->assertEquals('OPEN', $this->periodPrev->fresh()->status);
    }

    /**
     * Test 14: HTTP Web routes, PDF and Excel exports.
     */
    public function test_web_routes_and_pdf_excel_exports(): void
    {
        // 1. Balance Sheet web view
        $response = $this->actingAs($this->adminUser)->get(route('accounting.statements.balance_sheet', [
            'period_id' => $this->periodCurrent->id,
        ]));
        $response->assertStatus(200);
        $response->assertViewIs('admin.accounting.statements.balance_sheet');

        // 2. Income Statement Nature web view
        $response = $this->actingAs($this->adminUser)->get(route('accounting.statements.nature', [
            'period_id' => $this->periodCurrent->id,
        ]));
        $response->assertStatus(200);
        $response->assertViewIs('admin.accounting.statements.income_statement_nature');

        // 3. Income Statement Function web view
        $response = $this->actingAs($this->adminUser)->get(route('accounting.statements.function', [
            'period_id' => $this->periodCurrent->id,
        ]));
        $response->assertStatus(200);
        $response->assertViewIs('admin.accounting.statements.income_statement_function');

        // 4. Period Closing dashboard web view
        $response = $this->actingAs($this->adminUser)->get(route('accounting.period_closing.index', [
            'fiscal_year' => 2026,
        ]));
        $response->assertStatus(200);
        $response->assertViewIs('admin.accounting.statements.closing.index');

        // 5. PDF Export (Balance Sheet)
        $response = $this->actingAs($this->adminUser)->get(route('accounting.statements.export_pdf', [
            'statement' => 'balance-sheet',
            'period_id' => $this->periodCurrent->id,
        ]));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));

        // 6. Excel Export (Income Statement Function)
        $response = $this->actingAs($this->adminUser)->get(route('accounting.statements.export_excel', [
            'statement' => 'function',
            'period_id' => $this->periodCurrent->id,
        ]));
        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('content-type'));
    }
}
