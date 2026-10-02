<?php

namespace Tests\Feature;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ProductiveActivity;
use App\Models\User;
use App\Services\Accounting\LedgerReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountingLedgerReportsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $restrictedUser;
    protected LedgerReportService $ledgerService;

    protected AccountingPeriod $periodPrev;
    protected AccountingPeriod $periodCurrent;

    protected ChartOfAccount $accountCash;
    protected ChartOfAccount $accountBank;
    protected ChartOfAccount $accountReceivable;
    protected ChartOfAccount $accountIgv;
    protected ChartOfAccount $accountSales;
    protected ChartOfAccount $accountExpense;

    protected ProductiveActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledgerService = app(LedgerReportService::class);

        // Ensure permissions exist
        $viewPerm = Permission::firstOrCreate(['name' => 'accounting.view'], ['descripcion' => 'Ver libros contables']);
        $exportPerm = Permission::firstOrCreate(['name' => 'accounting.export'], ['descripcion' => 'Exportar libros contables']);

        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleAdmin->givePermissionTo([$viewPerm, $exportPerm]);

        $this->adminUser = User::where('user', 'admin_ledger_test')->first() ?? User::firstOrCreate(
            ['user' => 'admin_ledger_test'],
            [
                'nombres'   => 'Admin Ledger Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->adminUser->syncRoles(['ADMIN']);
        $this->adminUser->givePermissionTo([$viewPerm, $exportPerm]);

        $this->restrictedUser = User::where('user', 'no_acct_user')->first() ?? User::firstOrCreate(
            ['user' => 'no_acct_user'],
            [
                'nombres'   => 'No Acct User',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->restrictedUser->syncRoles([]);
        $this->restrictedUser->syncPermissions([]);

        // Ensure Periods exist: Prior (2026-09) and Current (2026-10)
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

        // Ensure Core Chart of Accounts exist
        $this->accountCash = ChartOfAccount::firstOrCreate(
            ['code' => '1011'],
            [
                'name'              => 'Caja Central',
                'element'           => 1,
                'level'             => 4,
                'nature'            => AccountNature::DEBIT,
                'classification'    => AccountType::ACTIVO,
                'accepts_movements' => true,
                'allows_movement'   => true,
                'active'            => true,
            ]
        );

        $this->accountBank = ChartOfAccount::firstOrCreate(
            ['code' => '10411'],
            [
                'name'              => 'Banco de la Nación - Cta. Recaudadora RDR',
                'element'           => 1,
                'level'             => 5,
                'nature'            => AccountNature::DEBIT,
                'classification'    => AccountType::ACTIVO,
                'accepts_movements' => true,
                'allows_movement'   => true,
                'active'            => true,
            ]
        );

        $this->accountReceivable = ChartOfAccount::firstOrCreate(
            ['code' => '1212'],
            [
                'name'                 => 'Facturas, boletas emitidas en cartera',
                'element'              => 1,
                'level'                => 4,
                'nature'               => AccountNature::DEBIT,
                'classification'       => AccountType::ACTIVO,
                'accepts_movements'    => true,
                'allows_movement'      => true,
                'requires_third_party' => true,
                'active'               => true,
            ]
        );

        $this->accountIgv = ChartOfAccount::firstOrCreate(
            ['code' => '40111'],
            [
                'name'              => 'IGV - Cuenta propia (Débito Fiscal)',
                'element'           => 4,
                'level'             => 5,
                'nature'            => AccountNature::CREDIT,
                'classification'    => AccountType::PASIVO,
                'accepts_movements' => true,
                'allows_movement'   => true,
                'active'            => true,
            ]
        );

        $this->accountSales = ChartOfAccount::firstOrCreate(
            ['code' => '70111'],
            [
                'name'              => 'Venta de mercaderías generales',
                'element'           => 7,
                'level'             => 5,
                'nature'            => AccountNature::CREDIT,
                'classification'    => AccountType::INGRESOS,
                'accepts_movements' => true,
                'allows_movement'   => true,
                'active'            => true,
            ]
        );

        $this->accountExpense = ChartOfAccount::firstOrCreate(
            ['code' => '6591'],
            [
                'name'                 => 'Otros gastos de gestión operativos',
                'element'              => 6,
                'level'                => 4,
                'nature'               => AccountNature::DEBIT,
                'classification'       => AccountType::GASTOS_NATURALEZA,
                'accepts_movements'    => true,
                'allows_movement'      => true,
                'requires_cost_center' => true,
                'active'               => true,
            ]
        );

        // Ensure Cost Center (Productive Activity) exists
        $this->activity = ProductiveActivity::firstOrCreate(
            ['code' => 'APE-PALMA-01'],
            [
                'name'        => 'Actividad Productiva Palma Aceitera',
                'status'      => 'ACTIVA',
            ]
        );
    }

    /**
     * Helper to create a posted journal entry.
     */
    protected function createPostedEntry(
        AccountingPeriod $period,
        string $date,
        string $number,
        VoucherType $type,
        string $concept,
        array $lines
    ): JournalEntry {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        $entry = JournalEntry::create([
            'accounting_period_id' => $period->id,
            'entry_number'         => $number,
            'entry_date'           => $date,
            'entry_type'           => $type,
            'concept'              => $concept,
            'currency'             => 'PEN',
            'exchange_rate'        => 1.0000,
            'total_debit'          => round($totalDebit, 2),
            'total_credit'         => round($totalCredit, 2),
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->adminUser->id,
            'posted_by_user_id'    => $this->adminUser->id,
            'posted_at'            => now(),
        ]);

        $lineNum = 1;
        foreach ($lines as $l) {
            JournalEntryLine::create([
                'journal_entry_id'     => $entry->id,
                'line_number'          => $lineNum++,
                'account_id'           => $l['account_id'],
                'debit'                => (float) ($l['debit'] ?? 0),
                'credit'               => (float) ($l['credit'] ?? 0),
                'glosa'                => $l['glosa'] ?? $concept,
                'third_party_document' => $l['third_party_document'] ?? null,
                'third_party_id'       => $l['third_party_id'] ?? null,
                'third_party_type'     => $l['third_party_type'] ?? null,
                'cost_center_id'       => $l['cost_center_id'] ?? null,
                'document_reference'   => $l['document_reference'] ?? null,
            ]);
        }

        return $entry;
    }

    public function test_general_journal_listing_and_totals(): void
    {
        // Entry 1: Sale
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-02',
            '2026-10-000101',
            VoucherType::OPERATING,
            'Venta de palma comercial',
            [
                ['account_id' => $this->accountReceivable->id, 'debit' => 118.00, 'credit' => 0.00, 'document_reference' => 'F001-0001'],
                ['account_id' => $this->accountIgv->id,        'debit' => 0.00,   'credit' => 18.00],
                ['account_id' => $this->accountSales->id,      'debit' => 0.00,   'credit' => 100.00],
            ]
        );

        // Entry 2: Collection
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-03',
            '2026-10-000102',
            VoucherType::OPERATING,
            'Cobranza en caja central',
            [
                ['account_id' => $this->accountCash->id,       'debit' => 118.00, 'credit' => 0.00],
                ['account_id' => $this->accountReceivable->id, 'debit' => 0.00,   'credit' => 118.00, 'document_reference' => 'F001-0001'],
            ]
        );

        $report = $this->ledgerService->getGeneralJournal(['period_id' => $this->periodCurrent->id]);

        $this->assertCount(2, $report['entries']);
        $this->assertEquals(236.00, $report['grand_total_debit']);
        $this->assertEquals(236.00, $report['grand_total_credit']);
        $this->assertTrue($report['is_balanced']);

        // Check sequential numbering
        $this->assertEquals(1, $report['entries'][0]['seq_number']);
        $this->assertEquals(2, $report['entries'][1]['seq_number']);
        $this->assertEquals('2026-10-000101', $report['entries'][0]['entry_number']);
        $this->assertEquals('2026-10-000102', $report['entries'][1]['entry_number']);
    }

    public function test_general_journal_filtering_by_voucher_type_and_dates(): void
    {
        // Entry 1: OPENING
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-01',
            '2026-10-000001',
            VoucherType::OPENING,
            'Asiento de Apertura',
            [
                ['account_id' => $this->accountCash->id,  'debit' => 500.00, 'credit' => 0.00],
                ['account_id' => $this->accountSales->id, 'debit' => 0.00,   'credit' => 500.00],
            ]
        );

        // Entry 2: OPERATING
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-15',
            '2026-10-000002',
            VoucherType::OPERATING,
            'Gasto de campo con caja',
            [
                ['account_id' => $this->accountExpense->id, 'debit' => 50.00, 'credit' => 0.00],
                ['account_id' => $this->accountCash->id,    'debit' => 0.00,  'credit' => 50.00],
            ]
        );

        // Filter by voucher type OPENING
        $resOpening = $this->ledgerService->getGeneralJournal([
            'period_id'    => $this->periodCurrent->id,
            'voucher_type' => VoucherType::OPENING->value,
        ]);
        $this->assertCount(1, $resOpening['entries']);
        $this->assertEquals('2026-10-000001', $resOpening['entries'][0]['entry_number']);

        // Filter by date range (2026-10-10 to 2026-10-20)
        $resDate = $this->ledgerService->getGeneralJournal([
            'date_from' => '2026-10-10',
            'date_to'   => '2026-10-20',
        ]);
        $this->assertCount(1, $resDate['entries']);
        $this->assertEquals('2026-10-000002', $resDate['entries'][0]['entry_number']);
    }

    public function test_general_ledger_opening_and_running_balance(): void
    {
        // 1. Transaction in PREVIOUS period (2026-09-15) -> creates opening balance for October
        $this->createPostedEntry(
            $this->periodPrev,
            '2026-09-15',
            '2026-09-000010',
            VoucherType::OPERATING,
            'Venta crédito septiembre',
            [
                ['account_id' => $this->accountReceivable->id, 'debit' => 500.00, 'credit' => 0.00],
                ['account_id' => $this->accountSales->id,      'debit' => 0.00,   'credit' => 500.00],
            ]
        );

        // 2. Transactions in CURRENT period (October)
        // Tx A: New sale (+200 debit on 1212)
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-05',
            '2026-10-000050',
            VoucherType::OPERATING,
            'Venta crédito octubre',
            [
                ['account_id' => $this->accountReceivable->id, 'debit' => 200.00, 'credit' => 0.00],
                ['account_id' => $this->accountSales->id,      'debit' => 0.00,   'credit' => 200.00],
            ]
        );

        // Tx B: Partial payment (-150 credit on 1212)
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-10',
            '2026-10-000051',
            VoucherType::OPERATING,
            'Abono parcial cliente',
            [
                ['account_id' => $this->accountCash->id,       'debit' => 150.00, 'credit' => 0.00],
                ['account_id' => $this->accountReceivable->id, 'debit' => 0.00,   'credit' => 150.00],
            ]
        );

        // Query General Ledger for October
        $report = $this->ledgerService->getGeneralLedger([
            'account_code' => '1212',
            'date_from'    => '2026-10-01',
            'date_to'      => '2026-10-31',
        ]);

        $this->assertArrayHasKey('1212', $report['accounts']);
        $acc = $report['accounts']['1212'];

        // Opening balance from September must be exactly 500.00
        $this->assertEquals(500.00, $acc['opening_balance']);

        // Transactions in October: 2 lines
        $this->assertCount(2, $acc['transactions']);

        // Running balance after Tx A (500 + 200 = 700)
        $this->assertEquals(200.00, $acc['transactions'][0]['debit']);
        $this->assertEquals(700.00, $acc['transactions'][0]['running_balance']);

        // Running balance after Tx B (700 - 150 = 550)
        $this->assertEquals(150.00, $acc['transactions'][1]['credit']);
        $this->assertEquals(550.00, $acc['transactions'][1]['running_balance']);

        // Final balance must be 550.00
        $this->assertEquals(200.00, $acc['period_debit']);
        $this->assertEquals(150.00, $acc['period_credit']);
        $this->assertEquals(550.00, $acc['final_balance']);
        $this->assertEquals(550.00, $acc['saldo_deudor']);
        $this->assertEquals(0.00, $acc['saldo_acreedor']);
    }

    public function test_general_ledger_filters_by_cost_center_and_third_party(): void
    {
        // Entry with Cost Center and Third Party
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-12',
            '2026-10-000080',
            VoucherType::OPERATING,
            'Gasto de campo asignado a Palma',
            [
                [
                    'account_id'           => $this->accountExpense->id,
                    'debit'                => 80.00,
                    'credit'               => 0.00,
                    'cost_center_id'       => $this->activity->id,
                    'third_party_document' => '20601234567',
                ],
                [
                    'account_id' => $this->accountCash->id,
                    'debit'      => 0.00,
                    'credit'     => 80.00,
                ],
            ]
        );

        // Filter by cost_center_id
        $resCC = $this->ledgerService->getGeneralLedger([
            'account_code'   => $this->accountExpense->code,
            'cost_center_id' => $this->activity->id,
            'period_id'      => $this->periodCurrent->id,
        ]);

        $this->assertArrayHasKey($this->accountExpense->code, $resCC['accounts']);
        $this->assertCount(1, $resCC['accounts'][$this->accountExpense->code]['transactions']);
        $this->assertEquals(80.00, $resCC['accounts'][$this->accountExpense->code]['transactions'][0]['debit']);

        // Filter by third_party_document
        $resThird = $this->ledgerService->getGeneralLedger([
            'account_code'         => $this->accountExpense->code,
            'third_party_document' => '20601234567',
            'period_id'            => $this->periodCurrent->id,
        ]);
        $this->assertCount(1, $resThird['accounts'][$this->accountExpense->code]['transactions']);

        // Filter by different third_party_document
        $resNone = $this->ledgerService->getGeneralLedger([
            'account_code'         => $this->accountExpense->code,
            'third_party_document' => '99999999999',
            'period_id'            => $this->periodCurrent->id,
        ]);
        $this->assertEmpty($resNone['accounts']);
    }

    public function test_trial_balance_debit_credit_sums_and_balances(): void
    {
        // Post several balanced operations
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-02',
            '2026-10-000201',
            VoucherType::OPERATING,
            'Venta de palma',
            [
                ['account_id' => $this->accountReceivable->id, 'debit' => 236.00, 'credit' => 0.00],
                ['account_id' => $this->accountIgv->id,        'debit' => 0.00,   'credit' => 36.00],
                ['account_id' => $this->accountSales->id,      'debit' => 0.00,   'credit' => 200.00],
            ]
        );

        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-04',
            '2026-10-000202',
            VoucherType::OPERATING,
            'Cobro a banco RDR',
            [
                ['account_id' => $this->accountBank->id,       'debit' => 236.00, 'credit' => 0.00],
                ['account_id' => $this->accountReceivable->id, 'debit' => 0.00,   'credit' => 236.00],
            ]
        );

        $tb = $this->ledgerService->getTrialBalance([
            'period_id' => $this->periodCurrent->id,
            'digits'    => 2,
        ]);

        $this->assertTrue($tb['verification']['is_balanced']);
        $this->assertEquals(0.00, $tb['verification']['debit_credit_diff']);
        $this->assertEquals(0.00, $tb['verification']['balances_diff']);
        $this->assertEquals('CUADRADO', $tb['verification']['status']);

        // Check sum totals
        $this->assertEquals(472.00, $tb['totals']['total_debit']);
        $this->assertEquals(472.00, $tb['totals']['total_credit']);
        $this->assertEquals(236.00, $tb['totals']['saldo_deudor']);
        $this->assertEquals(236.00, $tb['totals']['saldo_acreedor']);
    }

    public function test_trial_balance_aggregation_levels_2_3_4_digits(): void
    {
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-02',
            '2026-10-000301',
            VoucherType::OPERATING,
            'Operación para niveles de balance',
            [
                ['account_id' => $this->accountBank->id,       'debit' => 118.00, 'credit' => 0.00],
                ['account_id' => $this->accountIgv->id,        'debit' => 0.00,   'credit' => 18.00],
                ['account_id' => $this->accountSales->id,      'debit' => 0.00,   'credit' => 100.00],
            ]
        );

        // 2 Digits
        $tb2 = $this->ledgerService->getTrialBalance(['period_id' => $this->periodCurrent->id, 'digits' => 2]);
        $codes2 = collect($tb2['accounts'])->pluck('code')->all();
        $this->assertContains('10', $codes2);
        $this->assertContains('40', $codes2);
        $this->assertContains('70', $codes2);
        $this->assertTrue($tb2['verification']['is_balanced']);

        // 3 Digits
        $tb3 = $this->ledgerService->getTrialBalance(['period_id' => $this->periodCurrent->id, 'digits' => 3]);
        $codes3 = collect($tb3['accounts'])->pluck('code')->all();
        $this->assertContains('104', $codes3);
        $this->assertContains('401', $codes3);
        $this->assertContains('701', $codes3);
        $this->assertTrue($tb3['verification']['is_balanced']);

        // 4 Digits
        $tb4 = $this->ledgerService->getTrialBalance(['period_id' => $this->periodCurrent->id, 'digits' => 4]);
        $codes4 = collect($tb4['accounts'])->pluck('code')->all();
        $this->assertContains('1041', $codes4);
        $this->assertContains('4011', $codes4);
        $this->assertContains('7011', $codes4);
        $this->assertTrue($tb4['verification']['is_balanced']);
    }

    /**
     * CRITICAL ACCEPTANCE TEST:
     * "Trial Balance reconciles with test entries; General Ledger balance matches Trial Balance balance."
     */
    public function test_general_ledger_balance_strictly_matches_trial_balance_balance(): void
    {
        // 1. Initial balance in previous period
        $this->createPostedEntry(
            $this->periodPrev,
            '2026-09-20',
            '2026-09-000099',
            VoucherType::OPERATING,
            'Saldo inicial septiembre',
            [
                ['account_id' => $this->accountReceivable->id, 'debit' => 1000.00, 'credit' => 0.00],
                ['account_id' => $this->accountSales->id,      'debit' => 0.00,    'credit' => 1000.00],
            ]
        );

        // 2. Transactions in October
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-05',
            '2026-10-000401',
            VoucherType::OPERATING,
            'Nueva venta',
            [
                ['account_id' => $this->accountReceivable->id, 'debit' => 354.00, 'credit' => 0.00],
                ['account_id' => $this->accountIgv->id,        'debit' => 0.00,   'credit' => 54.00],
                ['account_id' => $this->accountSales->id,      'debit' => 0.00,   'credit' => 300.00],
            ]
        );

        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-15',
            '2026-10-000402',
            VoucherType::OPERATING,
            'Cobranza en efectivo',
            [
                ['account_id' => $this->accountCash->id,       'debit' => 600.00, 'credit' => 0.00],
                ['account_id' => $this->accountReceivable->id, 'debit' => 0.00,   'credit' => 600.00],
            ]
        );

        // Query General Ledger for account 1212
        $ledgerReport = $this->ledgerService->getGeneralLedger([
            'account_code' => '1212',
            'date_from'    => '2026-10-01',
            'date_to'      => '2026-10-31',
        ]);
        $ledgerAcc = $ledgerReport['accounts']['1212'];
        $ledgerFinalBalance = $ledgerAcc['final_balance'];

        // Initial 1000 + Debit 354 - Credit 600 = 754.00
        $this->assertEquals(754.00, $ledgerFinalBalance);

        // Query Trial Balance with date_from = 2026-10-01 (includes initial balance before date_from)
        $trialBalance = $this->ledgerService->getTrialBalance([
            'date_from' => '2026-10-01',
            'date_to'   => '2026-10-31',
            'digits'    => 4,
        ]);
        $this->assertTrue($trialBalance['verification']['is_balanced']);

        $tbAccount = collect($trialBalance['accounts'])->firstWhere('code', '1212');
        $this->assertNotNull($tbAccount);

        $trialBalanceNet = round((float) $tbAccount['saldo_deudor'] - (float) $tbAccount['saldo_acreedor'], 2);

        // ACCEPTANCE ASSERTION: General Ledger balance strictly matches Trial Balance balance
        $this->assertEquals($trialBalanceNet, $ledgerFinalBalance);
        $this->assertEquals(754.00, $trialBalanceNet);

        // Test reconciliation helper method
        $reconciliation = $this->ledgerService->reconcileAccount('1212', [
            'date_from' => '2026-10-01',
            'date_to'   => '2026-10-31',
        ]);

        $this->assertTrue($reconciliation['is_reconciled']);
        $this->assertEquals(0.00, $reconciliation['difference']);
        $this->assertEquals(754.00, $reconciliation['ledger_final_balance']);
        $this->assertEquals(754.00, $reconciliation['trial_balance_net']);
    }

    public function test_routes_require_authentication(): void
    {
        $this->get(route('accounting.journal.index'))->assertRedirect(route('login'));
        $this->get(route('accounting.ledger.index'))->assertRedirect(route('login'));
        $this->get(route('accounting.trial_balance.index'))->assertRedirect(route('login'));
    }

    public function test_routes_enforce_permissions(): void
    {
        // User without accounting.view should receive 403 Forbidden
        $this->actingAs($this->restrictedUser)
            ->get(route('accounting.journal.index'))
            ->assertForbidden();

        $this->actingAs($this->restrictedUser)
            ->get(route('accounting.ledger.index'))
            ->assertForbidden();

        $this->actingAs($this->restrictedUser)
            ->get(route('accounting.trial_balance.index'))
            ->assertForbidden();

        // User without accounting.export should receive 403 Forbidden on export endpoints
        $this->actingAs($this->restrictedUser)
            ->get(route('accounting.journal.pdf'))
            ->assertForbidden();

        $this->actingAs($this->restrictedUser)
            ->get(route('accounting.ledger.excel'))
            ->assertForbidden();

        // Authorized user can access all views and data endpoints
        $this->actingAs($this->adminUser)
            ->get(route('accounting.journal.index'))
            ->assertOk()
            ->assertViewIs('admin.accounting.reports.journal');

        $this->actingAs($this->adminUser)
            ->get(route('accounting.journal.data'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($this->adminUser)
            ->get(route('accounting.ledger.index'))
            ->assertOk()
            ->assertViewIs('admin.accounting.reports.ledger');

        $this->actingAs($this->adminUser)
            ->get(route('accounting.ledger.data'))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($this->adminUser)
            ->get(route('accounting.trial_balance.index'))
            ->assertOk()
            ->assertViewIs('admin.accounting.reports.trial_balance');

        $this->actingAs($this->adminUser)
            ->get(route('accounting.trial_balance.data'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_pdf_and_excel_export_endpoints(): void
    {
        // Create an entry so reports have data
        $this->createPostedEntry(
            $this->periodCurrent,
            '2026-10-02',
            '2026-10-000501',
            VoucherType::OPERATING,
            'Export test entry',
            [
                ['account_id' => $this->accountCash->id,  'debit' => 100.00, 'credit' => 0.00],
                ['account_id' => $this->accountSales->id, 'debit' => 0.00,   'credit' => 100.00],
            ]
        );

        // General Journal PDF (A4 landscape)
        $resPdfJournal = $this->actingAs($this->adminUser)
            ->get(route('accounting.journal.pdf', ['period_id' => $this->periodCurrent->id]));
        $resPdfJournal->assertOk();
        $this->assertStringContainsString('application/pdf', $resPdfJournal->headers->get('content-type'));

        // General Journal Excel
        $resExcelJournal = $this->actingAs($this->adminUser)
            ->get(route('accounting.journal.excel', ['period_id' => $this->periodCurrent->id]));
        $resExcelJournal->assertOk();

        // General Ledger PDF
        $resPdfLedger = $this->actingAs($this->adminUser)
            ->get(route('accounting.ledger.pdf', ['period_id' => $this->periodCurrent->id]));
        $resPdfLedger->assertOk();
        $this->assertStringContainsString('application/pdf', $resPdfLedger->headers->get('content-type'));

        // General Ledger Excel
        $resExcelLedger = $this->actingAs($this->adminUser)
            ->get(route('accounting.ledger.excel', ['period_id' => $this->periodCurrent->id]));
        $resExcelLedger->assertOk();

        // Trial Balance PDF
        $resPdfTb = $this->actingAs($this->adminUser)
            ->get(route('accounting.trial_balance.pdf', ['period_id' => $this->periodCurrent->id]));
        $resPdfTb->assertOk();
        $this->assertStringContainsString('application/pdf', $resPdfTb->headers->get('content-type'));

        // Trial Balance Excel
        $resExcelTb = $this->actingAs($this->adminUser)
            ->get(route('accounting.trial_balance.excel', ['period_id' => $this->periodCurrent->id]));
        $resExcelTb->assertOk();
    }
}
