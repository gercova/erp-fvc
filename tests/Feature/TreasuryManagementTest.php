<?php

namespace Tests\Feature;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\AccountPayable;
use App\Models\ActivityOrder;
use App\Models\ArchingCash;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankReconciliationItem;
use App\Models\Billing;
use App\Models\Buy;
use App\Models\Cash;
use App\Models\CashFlowMapping;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\Currency;
use App\Models\FundSource;
use App\Models\InternalTransfer;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PayMode;
use App\Models\Product;
use App\Models\ProductiveActivity;
use App\Models\ProductiveUnit;
use App\Models\ProducedItem;
use App\Models\Provider;
use App\Models\RdrBankReconciliation;
use App\Models\TypeDocument;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\JournalPostingService;
use App\Services\Treasury\AgingReportService;
use App\Services\Treasury\BankReconciliationService;
use App\Services\Treasury\BankStatementImportService;
use App\Services\Treasury\CashFlowReportService;
use App\Services\Treasury\InternalTransferService;
use Carbon\Carbon;
use Database\Seeders\CashFlowMappingSeeder;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TreasuryManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected AccountingPeriod $period;

    protected FundSource $fundBn;
    protected FundSource $fundCoop;
    protected FundSource $fundCut;

    protected ChartOfAccount $accountCash;
    protected ChartOfAccount $accountBankBn;
    protected ChartOfAccount $accountBankCoop;
    protected ChartOfAccount $accountCut;
    protected ChartOfAccount $accountReceivable;
    protected ChartOfAccount $accountPayable;
    protected ChartOfAccount $accountSales;
    protected ChartOfAccount $accountExpense;

    protected BankAccount $bankAccountBn;
    protected BankAccount $bankAccountCoop;

    protected ProductiveActivity $activity;
    protected Client $client;
    protected Provider $provider;

    protected BankStatementImportService $importService;
    protected BankReconciliationService $reconciliationService;
    protected AgingReportService $agingService;
    protected CashFlowReportService $cashFlowService;
    protected InternalTransferService $transferService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importService = app(BankStatementImportService::class);
        $this->reconciliationService = app(BankReconciliationService::class);
        $this->agingService = app(AgingReportService::class);
        $this->cashFlowService = app(CashFlowReportService::class);
        $this->transferService = app(InternalTransferService::class);

        // Ensure permissions
        $viewPerm = Permission::firstOrCreate(['name' => 'accounting.view'], ['descripcion' => 'Ver contabilidad']);
        $exportPerm = Permission::firstOrCreate(['name' => 'accounting.export'], ['descripcion' => 'Exportar contabilidad']);

        $roleAdmin = Role::firstOrCreate(['name' => 'ADMIN']);
        $roleAdmin->givePermissionTo([$viewPerm, $exportPerm]);

        $this->adminUser = User::where('user', 'admin_treasury_test')->first() ?? User::firstOrCreate(
            ['user' => 'admin_treasury_test'],
            [
                'nombres'   => 'Admin Treasury Test',
                'password'  => bcrypt('password123'),
                'estado'    => 1,
                'idcaja'    => 1,
                'idalmacen' => 1,
            ]
        );
        $this->adminUser->syncRoles(['ADMIN']);
        $this->adminUser->givePermissionTo([$viewPerm, $exportPerm]);

        // Accounting Period
        $this->period = AccountingPeriod::firstOrCreate(
            ['period_code' => '2026-10'],
            [
                'fiscal_year' => 2026,
                'month'       => 10,
                'start_date'  => '2026-10-01',
                'end_date'    => '2026-10-31',
                'status'      => 'OPEN',
            ]
        );

        // Chart of Accounts
        $this->accountCash = ChartOfAccount::where('code', '1011')->first() ?? ChartOfAccount::create([
            'code' => '1011', 'name' => 'Caja Central', 'element' => 1, 'level' => 4,
            'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts_movements' => true,
        ]);

        $this->accountBankBn = ChartOfAccount::where('code', '10411')->first() ?? ChartOfAccount::create([
            'code' => '10411', 'name' => 'Banco de la Nación - Cta. Recaudadora RDR', 'element' => 1, 'level' => 5,
            'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts_movements' => true,
        ]);

        $this->accountBankCoop = ChartOfAccount::where('code', '10412')->first() ?? ChartOfAccount::create([
            'code' => '10412', 'name' => 'Cooperativa Tocache', 'element' => 1, 'level' => 5,
            'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts_movements' => true,
        ]);

        $this->accountCut = ChartOfAccount::where('code', '1071')->first() ?? ChartOfAccount::create([
            'code' => '1071', 'name' => 'Cuenta Única del Tesoro (CUT)', 'element' => 1, 'level' => 4,
            'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts_movements' => true,
        ]);

        $this->accountReceivable = ChartOfAccount::where('code', '1212')->first() ?? ChartOfAccount::create([
            'code' => '1212', 'name' => 'Facturas por Cobrar', 'element' => 1, 'level' => 4,
            'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts_movements' => true,
        ]);

        $this->accountPayable = ChartOfAccount::where('code', '4212')->first() ?? ChartOfAccount::create([
            'code' => '4212', 'name' => 'Facturas por Pagar', 'element' => 4, 'level' => 4,
            'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts_movements' => true,
        ]);

        $this->accountSales = ChartOfAccount::where('code', '70111')->first() ?? ChartOfAccount::create([
            'code' => '70111', 'name' => 'Venta de Mercaderías', 'element' => 7, 'level' => 5,
            'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts_movements' => true,
        ]);

        $this->accountExpense = ChartOfAccount::where('code', '6391')->first() ?? ChartOfAccount::create([
            'code' => '6391', 'name' => 'Gastos Bancarios y Comisiones', 'element' => 6, 'level' => 4,
            'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts_movements' => true,
        ]);

        // Fund Sources
        $this->fundBn = FundSource::firstOrCreate(
            ['code' => 'RDR-BN-TEST'],
            [
                'name'            => 'Recaudación Banco de la Nación',
                'bank_name'       => 'Banco de la Nación',
                'account_number'  => '00-068-999991',
                'currency'        => 'PEN',
                'initial_balance' => 10000.00,
                'current_balance' => 10000.00,
                'is_cut'          => false,
                'is_active'       => true,
            ]
        );

        $this->fundCoop = FundSource::firstOrCreate(
            ['code' => 'RDR-COOP-TEST'],
            [
                'name'            => 'Cooperativa Tocache Operativa',
                'bank_name'       => 'Cooperativa Tocache',
                'account_number'  => '101-001-888882',
                'currency'        => 'PEN',
                'initial_balance' => 5000.00,
                'current_balance' => 5000.00,
                'is_cut'          => false,
                'is_active'       => true,
            ]
        );

        $this->fundCut = FundSource::firstOrCreate(
            ['code' => 'CUT-TEST'],
            [
                'name'            => 'Cuenta Única del Tesoro Nacional',
                'bank_name'       => 'Banco de la Nación - CUT',
                'account_number'  => '00-000-CUT-777',
                'currency'        => 'PEN',
                'initial_balance' => 20000.00,
                'current_balance' => 20000.00,
                'is_cut'          => true,
                'is_active'       => true,
            ]
        );

        // Bank Accounts (linked to fund sources and PCGE accounts)
        $this->bankAccountBn = BankAccount::firstOrCreate(
            ['fund_source_id' => $this->fundBn->id],
            [
                'account_number'        => '00-068-999991',
                'cci'                   => '018-068-000068999991-55',
                'bank_name'             => 'Banco de la Nación',
                'account_type'          => 'CURRENT',
                'currency'              => 'PEN',
                'accounting_account_id' => $this->accountBankBn->id,
                'initial_balance'       => 10000.00,
                'current_balance'       => 10000.00,
                'is_active'             => true,
            ]
        );

        $this->bankAccountCoop = BankAccount::firstOrCreate(
            ['fund_source_id' => $this->fundCoop->id],
            [
                'account_number'        => '101-001-888882',
                'bank_name'             => 'Cooperativa Tocache',
                'account_type'          => 'SAVINGS',
                'currency'              => 'PEN',
                'accounting_account_id' => $this->accountBankCoop->id,
                'initial_balance'       => 5000.00,
                'current_balance'       => 5000.00,
                'is_active'             => true,
            ]
        );

        // Client and Provider
        $this->client = Client::firstOrCreate(
            ['nro_documento' => '20601234567'],
            [
                'nombres'   => 'AGROEXPORTADORA DEL ORIENTE S.A.C.',
                'direccion' => 'Av. Marginal Sur 1050',
            ]
        );

        $this->provider = Provider::firstOrCreate(
            ['nro_documento' => '20491234568'],
            [
                'nombres'     => 'FERTILIZANTES Y ABONOS DEL VALLE S.A.',
                'direccion'   => 'Jr. Los Cedros 340',
                'codigo_pais' => 'PE',
            ]
        );

        // Productive Activity and Unit
        $this->activity = ProductiveActivity::firstOrCreate(
            ['code' => 'ACT-CACAO-2026'],
            [
                'name'             => 'Módulo Cacao CCN-51',
                'cost_center_code' => 'CC-CACAO',
                'status'           => 'ACTIVA',
            ]
        );

        $unit = ProductiveUnit::firstOrCreate(
            [
                'productive_activity_id' => $this->activity->id,
                'code'                   => 'UNIT-AGRO-TEST',
            ],
            [
                'name'   => 'Unidad Agrícola Experimental',
                'status' => 'ACTIVA',
            ]
        );

        // Seed CashFlowMappings
        (new CashFlowMappingSeeder())->run();
    }

    /**
     * 1. Deliverable 1: bank_accounts linked to fund_sources and 104x PCGE account.
     */
    public function test_bank_account_is_linked_to_fund_source_and_accounting_account_104x(): void
    {
        $this->assertNotNull($this->bankAccountBn);
        $this->assertEquals($this->fundBn->id, $this->bankAccountBn->fund_source_id);
        $this->assertEquals($this->accountBankBn->id, $this->bankAccountBn->accounting_account_id);
        $this->assertTrue(str_starts_with($this->bankAccountBn->accountingAccount->code, '104'));
        $this->assertEquals(10000.00, (float) $this->bankAccountBn->current_balance);
    }

    /**
     * 2. Deliverable 1: bank_movements import with template and deduplication (date + reference + amount).
     */
    public function test_bank_movements_import_with_template_and_deduplication(): void
    {
        $rows = [
            [
                'fecha'            => '2026-10-05',
                'numero_operacion' => 'OP-100201',
                'tipo'             => 'INFLOW',
                'monto'            => 1500.00,
                'concepto'         => 'Abono de cliente por venta de granos',
            ],
            [
                'fecha'            => '2026-10-06',
                'numero_operacion' => 'OP-100202',
                'tipo'             => 'OUTFLOW',
                'monto'            => 450.00,
                'concepto'         => 'Pago de flete y transporte de insumos',
            ],
            [
                'fecha'            => '2026-10-07',
                'numero_operacion' => 'ITF-001',
                'tipo'             => 'OUTFLOW',
                'monto'            => 0.75,
                'concepto'         => 'Impuesto ITF débito',
            ],
        ];

        // 1st Import: 3 movements should be recorded
        $result1 = $this->importService->import($this->bankAccountBn, $rows);

        $this->assertEquals(3, $result1['imported_count']);
        $this->assertEquals(0, $result1['duplicates_skipped']);

        // 2nd Import: same 3 rows + 1 new row -> 1 imported, 3 skipped!
        $rows2 = $rows;
        $rows2[] = [
            'fecha'            => '2026-10-08',
            'numero_operacion' => 'OP-100203',
            'tipo'             => 'INFLOW',
            'monto'            => 800.00,
            'concepto'         => 'Nuevo abono en efectivo por ventanilla',
        ];

        $result2 = $this->importService->import($this->bankAccountBn, $rows2);

        $this->assertEquals(1, $result2['imported_count']);
        $this->assertEquals(3, $result2['duplicates_skipped']);

        // Assert total in DB is 4
        $count = BankMovement::where('bank_account_id', $this->bankAccountBn->id)->count();
        $this->assertEquals(4, $count);
    }

    /**
     * 3. Deliverable 2: Bank reconciliation workflow: suggestions matching.
     */
    public function test_bank_reconciliation_automatic_match_suggestions(): void
    {
        // 1. Post a journal entry for a sales collection on 10411 (S/ 2500.00)
        $journalService = app(JournalPostingService::class);
        $entry = $journalService->postRaw(
            [
                'entry_date' => '2026-10-10',
                'entry_type' => VoucherType::OPERATING,
                'concept'    => 'Cobranza factura cliente OP-778899',
            ],
            [
                [
                    'account_id'         => $this->accountBankBn->id,
                    'debit'              => 2500.00,
                    'credit'             => 0.00,
                    'bank_account_id'    => $this->bankAccountBn->id,
                    'fund_source_id'     => $this->fundBn->id,
                    'document_reference' => 'OP-778899',
                    'glosa'              => 'Cobranza cliente OP-778899',
                ],
                [
                    'account_id'         => $this->accountReceivable->id,
                    'debit'              => 0.00,
                    'credit'             => 2500.00,
                    'glosa'              => 'Cancelación factura por cobrar',
                ],
            ]
        );

        $this->assertNotNull($entry);

        // 2. Import a bank movement matching date ± 1 day and reference OP-778899
        $movement = BankMovement::create([
            'bank_account_id'       => $this->bankAccountBn->id,
            'movement_date'         => '2026-10-11',
            'operation_number'      => 'OP-778899',
            'movement_type'         => 'INFLOW',
            'amount'                => 2500.00,
            'concept'               => 'Depósito cliente ventanilla OP-778899',
            'reconciliation_status' => 'PENDING',
        ]);

        // 3. Initiate reconciliation
        $rec = $this->reconciliationService->initiateReconciliation(
            $this->bankAccountBn,
            2026,
            10,
            12500.00,
            '2026-10-31',
            $this->adminUser->id
        );

        // 4. Request suggestions
        $suggestions = $this->reconciliationService->getMatchSuggestions($rec, 3);

        $this->assertNotEmpty($suggestions);
        $suggestion = $suggestions->first();
        $this->assertEquals($movement->id, $suggestion['movement']->id);
        $this->assertEquals($entry->id, $suggestion['journal_entry']->id);
        $this->assertEquals('HIGH', $suggestion['confidence']);

        // 5. Match movement
        $item = $this->reconciliationService->matchMovement($rec, $movement, $entry);

        $this->assertEquals('MATCHED', $item->item_type);
        $this->assertEquals('MATCHED', $movement->fresh()->reconciliation_status);
        $this->assertEquals($entry->id, $movement->fresh()->matched_journal_entry_id);
    }

    /**
     * 4. Deliverable 2: Reconciling items and unrecorded bank charge adjustment entries.
     */
    public function test_reconciling_items_and_bank_charge_adjustment_entries(): void
    {
        // Initiate reconciliation with statement balance S/ 10200 and book balance S/ 10000
        $rec = $this->reconciliationService->initiateReconciliation(
            $this->bankAccountBn,
            2026,
            10,
            10200.00,
            '2026-10-31',
            $this->adminUser->id
        );

        // Add Deposit in transit: S/ 300 (+)
        $this->reconciliationService->addReconcilingItem(
            $rec,
            'DEPOSIT_IN_TRANSIT',
            300.00,
            'DEP-TRNS-1',
            'Depósito en tránsito registrado en libros'
        );

        // Add Outstanding Check: S/ 500 (-)
        $this->reconciliationService->addReconcilingItem(
            $rec,
            'OUTSTANDING_CHECK',
            500.00,
            'CHQ-OUT-1',
            'Cheque girado pendiente de cobro en banco'
        );

        // Add Bank Charge: S/ 20.00 (-)
        $chargeItem = $this->reconciliationService->addReconcilingItem(
            $rec,
            'BANK_CHARGE',
            20.00,
            'COMIS-99',
            'Comisión bancaria por mantenimiento de cuenta'
        );

        $rec->refresh();
        $this->assertEquals(300.00, (float) $rec->uncredited_deposits);
        $this->assertEquals(500.00, (float) $rec->outstanding_checks);
        $this->assertEquals(20.00, (float) $rec->unrecorded_bank_charges);

        // Now post adjustment journal entry for the bank charge of S/ 20.00
        $adjEntry = $this->reconciliationService->postBankChargeAdjustment(
            $rec,
            20.00,
            'Comisión bancaria por mantenimiento de cuenta',
            '6391',
            'COMIS-99',
            $chargeItem
        );

        $this->assertNotNull($adjEntry);
        $this->assertEquals(JournalStatus::POSTED, $adjEntry->status);
        $this->assertEquals(VoucherType::ADJUSTMENT, $adjEntry->entry_type);

        // Verify balanced double entry: Debit 6391 (S/ 20.00), Credit 10411 (S/ 20.00)
        $debitLine = $adjEntry->lines->where('account_id', $this->accountExpense->id)->first();
        $creditLine = $adjEntry->lines->where('account_id', $this->accountBankBn->id)->first();

        $this->assertNotNull($debitLine);
        $this->assertNotNull($creditLine);
        $this->assertEquals(20.00, (float) $debitLine->debit);
        $this->assertEquals(20.00, (float) $creditLine->credit);
    }

    /**
     * 5. ACCEPTANCE CRITERIA: Reconciled bank balance = accounting balance of the 104x account after reconciling items.
     * Closing with zero difference.
     */
    public function test_bank_reconciliation_zero_difference_closure_and_acceptance(): void
    {
        // 1. Post initial opening transaction on Bank 10411 of S/ 5000.00
        $journalService = app(JournalPostingService::class);
        $journalService->postRaw(
            ['entry_date' => '2026-10-01', 'concept' => 'Saldo operativo inicial en libros'],
            [
                ['account_id' => $this->accountBankBn->id, 'debit' => 5000.00, 'credit' => 0.00],
                ['account_id' => $this->accountSales->id, 'debit' => 0.00, 'credit' => 5000.00],
            ]
        );

        // Book balance is now Initial (10,000) + 5,000 = 15,000.00
        // Bank statement reports: 15,300.00
        $rec = $this->reconciliationService->initiateReconciliation(
            $this->bankAccountBn,
            2026,
            10,
            15300.00,
            '2026-10-31',
            $this->adminUser->id
        );

        $this->assertEquals(15000.00, (float) $rec->book_calculated_balance);
        $this->assertEquals(15300.00, (float) $rec->bank_statement_balance);

        // Reconciling items to bridge 15,300 bank vs 15,000 book:
        // Outstanding check: S/ 300.00 (Bank balance adjusted: 15,300 - 300 = 15,000.00)
        $this->reconciliationService->addReconcilingItem(
            $rec,
            'OUTSTANDING_CHECK',
            300.00,
            'CHQ-555',
            'Cheque en tránsito entregado a proveedor'
        );

        $rec->refresh();

        // Acceptance Check:
        // Adjusted Bank Balance = 15,300 - 300 = 15,000.00
        // Adjusted Book Balance = 15,000.00
        // Difference = 0.00!
        $this->assertEquals(0.00, (float) $rec->reconciled_difference);
        $this->assertEquals('BALANCED', $rec->status);

        // Close reconciliation
        $closed = $this->reconciliationService->closeReconciliation($rec, $this->adminUser->id);

        $this->assertEquals('BALANCED', $closed->status);
        $this->assertNotNull($closed->reconciled_at);
        $this->assertEquals($this->adminUser->id, $closed->approved_by_user_id);
    }

    /**
     * Test closing with non-zero difference is rejected.
     */
    public function test_closing_reconciliation_with_difference_is_rejected(): void
    {
        $rec = $this->reconciliationService->initiateReconciliation(
            $this->bankAccountBn,
            2026,
            10,
            20000.00, // Discrepant bank statement balance
            '2026-10-31',
            $this->adminUser->id
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('No se puede cerrar la conciliación bancaria con diferencia distinta de cero');

        $this->reconciliationService->closeReconciliation($rec, $this->adminUser->id);
    }

    /**
     * 6. Deliverable 3: Accounts receivable / payable aging views.
     */
    public function test_accounts_receivable_and_payable_aging_views(): void
    {
        // 1. Create Credit Billing for Client (overdue by 45 days -> bucket days_31_60)
        $typeDoc = TypeDocument::first() ?? TypeDocument::create(['codigo' => '01', 'descripcion' => 'FACTURA', 'estado' => 1]);
        $payMode = PayMode::first() ?? PayMode::create(['descripcion' => 'Credito']);

        Billing::create([
            'idtipo_comprobante' => $typeDoc->id,
            'serie'              => 'F001',
            'correlativo'        => '00009991',
            'fecha_emision'      => '2026-08-15',
            'fecha_vencimiento'  => '2026-08-30',
            'hora'               => '10:00:00',
            'idcliente'          => $this->client->id,
            'idmoneda'           => 1,
            'idpago'             => $payMode->id,
            'modo_pago'          => 2, // Credito
            'sunat_forma_pago'   => 'Credito',
            'total'              => 4200.00,
            'monto_credito'      => 4200.00,
            'idusuario'          => $this->adminUser->id,
        ]);

        // 2. Create Activity Order for Client (overdue by 15 days -> bucket current)
        $producedItem = ProducedItem::firstOrCreate(
            [
                'productive_activity_id' => $this->activity->id,
                'name'                   => 'Cacao en Grano Seco CCN-51',
            ],
            [
                'unit_of_measurement'   => 'KG',
                'standard_cost'         => 12.00,
            ]
        );

        ActivityOrder::create([
            'uuid'                   => 'order-uuid-test-01',
            'order_code'             => 'ORD-2026-001',
            'productive_activity_id' => $this->activity->id,
            'client_id'              => $this->client->id,
            'produced_item_id'       => $producedItem->id,
            'order_date'             => '2026-09-15',
            'expected_delivery_date' => '2026-09-25',
            'quantity'               => 100,
            'unit_price'             => 18.00,
            'total_amount'           => 1800.00,
            'advance_payment'        => 300.00,
            'balance_pending'        => 1500.00,
            'status'                 => 'delivered',
            'created_by_user_id'     => $this->adminUser->id,
        ]);

        // 3. Create Account Payable for Provider (overdue by 75 days -> bucket days_61_90)
        $buy = Buy::create([
            'idproveedor'        => $this->provider->id,
            'idusuario'          => $this->adminUser->id,
            'idtipo_comprobante' => 1,
            'serie'              => 'FC01',
            'correlativo'        => '00008881',
            'fecha_emision'      => '2026-07-10',
            'fecha_vencimiento'  => '2026-07-25',
            'hora'               => '11:00:00',
            'idmoneda'           => 1,
            'idpago'             => 1,
            'modo_pago'          => 2,
            'igv'                => 0.00,
            'gratuita'           => 0.00,
            'otros_cargos'       => 0.00,
            'total'              => 3500.00,
            'condicion_pago'     => 'Credito',
            'monto_credito'      => 3500.00,
        ]);

        AccountPayable::create([
            'idcompra'          => $buy->id,
            'idproveedor'       => $this->provider->id,
            'monto_total'       => 3500.00,
            'monto_pagado'      => 500.00,
            'saldo'             => 3000.00,
            'fecha_emision'     => '2026-07-10',
            'fecha_vencimiento' => '2026-07-25',
            'estado'            => 'PENDIENTE',
        ]);

        // Evaluate AR aging as of 2026-10-15
        $arReport = $this->agingService->getReceivablesAging(null, '2026-10-15');

        $this->assertEquals(5700.00, $arReport['grand_totals']['total_pending']); // 4200 + 1500
        $this->assertGreaterThan(0, $arReport['grand_totals']['days_31_60']); // Billing due Aug 30 -> 46 days overdue
        $this->assertGreaterThan(0, $arReport['grand_totals']['current']);    // Order due Sep 25 -> 20 days overdue

        // Evaluate AP aging as of 2026-10-15
        $apReport = $this->agingService->getPayablesAging(null, '2026-10-15');

        $this->assertEquals(3000.00, $apReport['grand_totals']['total_pending']);
        $this->assertGreaterThan(0, $apReport['grand_totals']['days_61_90']); // Buy due July 25 -> 82 days overdue
    }

    /**
     * 7. Deliverable 4 & ACCEPTANCE: Direct Cash Flow reconciles with change in cash and bank balances.
     */
    public function test_cash_flow_direct_method_reconciles_with_change_in_cash_and_bank(): void
    {
        $journalService = app(JournalPostingService::class);

        // 1. Inflow: Collections from customer sales (S/ 6,000.00) -> Debit Bank, Credit Sales
        $journalService->postRaw(
            ['entry_date' => '2026-10-05', 'concept' => 'Cobranza contado de clientes'],
            [
                ['account_id' => $this->accountBankBn->id, 'debit' => 6000.00, 'credit' => 0.00, 'bank_account_id' => $this->bankAccountBn->id],
                ['account_id' => $this->accountSales->id, 'debit' => 0.00, 'credit' => 6000.00],
            ]
        );

        // 2. Outflow: Operational expenses paid from bank (S/ 2,000.00) -> Debit Expense, Credit Bank
        $journalService->postRaw(
            ['entry_date' => '2026-10-12', 'concept' => 'Pago de servicios operativos'],
            [
                ['account_id' => $this->accountExpense->id, 'debit' => 2000.00, 'credit' => 0.00],
                ['account_id' => $this->accountBankBn->id, 'debit' => 0.00, 'credit' => 2000.00, 'bank_account_id' => $this->bankAccountBn->id],
            ]
        );

        // Generate Cash Flow Report for period 2026-10-01 to 2026-10-31
        $cf = $this->cashFlowService->generateDirectCashFlow('2026-10-01', '2026-10-31');

        $this->assertEquals(6000.00, $cf['summary']['actual_inflows']);
        $this->assertEquals(2000.00, $cf['summary']['actual_outflows']);
        $this->assertEquals(4000.00, $cf['summary']['actual_net_flow']);

        // ACCEPTANCE CRITERIA:
        // actual cash flow reconciles with the change in cash and bank balances for the period
        $this->assertEquals(4000.00, $cf['balance_change']);
        $this->assertTrue($cf['reconciliation']['is_reconciled']);
        $this->assertEquals(0.00, (float) $cf['reconciliation']['difference']);

        // Check CSV Export output
        $csv = $this->cashFlowService->exportToCsv($cf);
        $this->assertStringContainsString('FLUJO DE CAJA - METODO DIRECTO', $csv);
        $this->assertStringContainsString('ACTIVIDADES DE OPERACIÓN', $csv);
        $this->assertStringContainsString('CONCILIADO EXACTO', $csv);
    }

    /**
     * 8. Deliverable 5: Movements between accounts: Bank to Bank transfer with double entry.
     */
    public function test_internal_transfer_between_bank_accounts(): void
    {
        $initialBn = (float) $this->bankAccountBn->current_balance;
        $initialCoop = (float) $this->bankAccountCoop->current_balance;

        $transfer = $this->transferService->transferBetweenBankAccounts(
            $this->bankAccountBn,
            $this->bankAccountCoop,
            1200.00,
            '2026-10-15',
            'Transferencia para cobertura de pagos en Cooperativa',
            'TRF-BN-COOP-01',
            $this->adminUser->id
        );

        $this->assertNotNull($transfer);
        $this->assertEquals('COMPLETED', $transfer->status);
        $this->assertEquals(1200.00, (float) $transfer->amount);

        // Balances updated
        $this->assertEquals($initialBn - 1200.00, (float) $this->bankAccountBn->fresh()->current_balance);
        $this->assertEquals($initialCoop + 1200.00, (float) $this->bankAccountCoop->fresh()->current_balance);

        // Double journal entry verified:
        // Debit: Destination Bank Coop (10412) S/ 1200.00
        // Credit: Source Bank BN (10411) S/ 1200.00
        $entry = $transfer->journalEntry;
        $this->assertNotNull($entry);
        $this->assertEquals(JournalStatus::POSTED, $entry->status);

        $debitLine = $entry->lines->where('account_id', $this->accountBankCoop->id)->first();
        $creditLine = $entry->lines->where('account_id', $this->accountBankBn->id)->first();

        $this->assertNotNull($debitLine);
        $this->assertNotNull($creditLine);
        $this->assertEquals(1200.00, (float) $debitLine->debit);
        $this->assertEquals(1200.00, (float) $creditLine->credit);
    }

    /**
     * 9. Deliverable 5: Cash count deposit to bank account with double entry.
     */
    public function test_cash_count_deposit_to_bank_account(): void
    {
        $cash = Cash::first() ?? Cash::create(['nombre' => 'Caja Central', 'estado' => 1]);
        $arching = ArchingCash::create([
            'idcaja'       => $cash->id,
            'idusuario'    => $this->adminUser->id,
            'fecha_inicio' => '2026-10-16 08:00:00',
            'monto_inicial'=> 200.00,
            'total_ventas' => 0,
            'estado'       => 1,
        ]);

        $initialBankBalance = (float) $this->bankAccountBn->current_balance;

        $transfer = $this->transferService->depositCashToBank(
            $arching,
            $this->bankAccountBn,
            1800.00,
            '2026-10-16',
            'Depósito en ventanilla de recaudación diaria',
            'DEP-VOUCHER-88',
            $this->adminUser->id
        );

        $this->assertNotNull($transfer);
        $this->assertEquals('CASH_TO_BANK', $transfer->transfer_type);
        $this->assertEquals($initialBankBalance + 1800.00, (float) $this->bankAccountBn->fresh()->current_balance);

        // Double journal entry verified:
        // Debit: Bank 10411 (S/ 1800.00)
        // Credit: Cash 1011 (S/ 1800.00)
        $entry = $transfer->journalEntry;
        $this->assertNotNull($entry);

        $debitBank = $entry->lines->where('account_id', $this->accountBankBn->id)->first();
        $creditCash = $entry->lines->where('account_id', $this->accountCash->id)->first();

        $this->assertNotNull($debitBank);
        $this->assertNotNull($creditCash);
        $this->assertEquals(1800.00, (float) $debitBank->debit);
        $this->assertEquals(1800.00, (float) $creditCash->credit);
    }

    /**
     * 10. Deliverable 5: Transfer to CUT with double entry.
     */
    public function test_transfer_to_cut_with_double_journal_entry(): void
    {
        $initialBank = (float) $this->bankAccountBn->current_balance;
        $initialCut = (float) $this->fundCut->current_balance;

        $transfer = $this->transferService->transferToCut(
            $this->bankAccountBn,
            $this->fundCut,
            2500.00,
            '2026-10-18',
            'Reversión y transferencia mensual a la CUT',
            'CUT-PAPEL-101',
            $this->adminUser->id
        );

        $this->assertNotNull($transfer);
        $this->assertEquals('TRANSFER_TO_CUT', $transfer->transfer_type);

        $this->assertEquals($initialBank - 2500.00, (float) $this->bankAccountBn->fresh()->current_balance);
        $this->assertEquals($initialCut + 2500.00, (float) $this->fundCut->fresh()->current_balance);

        // Double journal entry:
        // Debit: CUT 1071 (S/ 2500.00)
        // Credit: Bank 10411 (S/ 2500.00)
        $entry = $transfer->journalEntry;
        $this->assertNotNull($entry);

        $debitCut = $entry->lines->where('account_id', $this->accountCut->id)->first();
        $creditBank = $entry->lines->where('account_id', $this->accountBankBn->id)->first();

        $this->assertNotNull($debitCut);
        $this->assertNotNull($creditBank);
        $this->assertEquals(2500.00, (float) $debitCut->debit);
        $this->assertEquals(2500.00, (float) $creditBank->credit);
    }

    /**
     * 11. Test HTTP routes and responses.
     */
    public function test_treasury_http_endpoints(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Bank Accounts index
        $resAccounts = $this->getJson(route('treasury.bank_accounts.index'));
        $resAccounts->assertOk();
        $resAccounts->assertJson(['success' => true]);

        // 2. Reconciliations index
        $resRecs = $this->getJson(route('treasury.reconciliations.index'));
        $resRecs->assertOk();
        $resRecs->assertJson(['success' => true]);

        // 3. Aging Receivables
        $resAr = $this->getJson(route('treasury.aging.receivables'));
        $resAr->assertOk();
        $resAr->assertJson(['success' => true]);

        // 4. Aging Payables
        $resAp = $this->getJson(route('treasury.aging.payables'));
        $resAp->assertOk();
        $resAp->assertJson(['success' => true]);

        // 5. Cash Flow data
        $resCf = $this->getJson(route('treasury.cash_flow.data'));
        $resCf->assertOk();
        $resCf->assertJson(['success' => true]);

        // 6. Internal Transfers index
        $resTrf = $this->getJson(route('treasury.transfers.index'));
        $resTrf->assertOk();
        $resTrf->assertJson(['success' => true]);

        // 7. Template CSV download
        $resTemplate = $this->get(route('treasury.bank_accounts.template'));
        $resTemplate->assertOk();
        $this->assertStringContainsString('numero_operacion', $resTemplate->getContent());
    }
}
