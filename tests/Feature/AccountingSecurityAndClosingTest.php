<?php

namespace Tests\Feature;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingAuditLog;
use App\Models\AccountingPeriod;
use App\Models\ChartOfAccount;
use App\Models\FundSource;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\BankAccount;
use App\Models\RdrBankReconciliation;
use App\Models\User;
use App\Services\Accounting\PeriodClosingService;
use App\Services\AccountingService;
use Database\Seeders\AccountingSecurityRoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountingSecurityAndClosingTest extends TestCase
{
    use DatabaseTransactions;

    protected AccountingService $accountingService;
    protected PeriodClosingService $closingService;

    protected User $superAdmin;
    protected User $contabilidadUser;
    protected User $administracionUser;
    protected User $directorUser;
    protected User $tesoreroUser;
    protected User $unauthorizedUser;

    protected AccountingPeriod $openPeriod;
    protected AccountingPeriod $closedPeriod;
    protected ChartOfAccount $account10;
    protected ChartOfAccount $account60;
    protected ChartOfAccount $account42;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = app(AccountingService::class);
        $this->closingService = app(PeriodClosingService::class);

        // Run Idempotent Seeder
        $this->seed(AccountingSecurityRoleSeeder::class);

        // Setup Users with Distinct Roles
        $this->superAdmin = User::firstOrCreate(
            ['user' => 'b7_superadmin'],
            ['nombres' => 'Super Admin Test', 'password' => bcrypt('secret'), 'estado' => 1, 'idcaja' => 1, 'idalmacen' => 1]
        );
        $this->superAdmin->syncRoles(['SUPERADMIN']);

        $this->contabilidadUser = User::firstOrCreate(
            ['user' => 'b7_contabilidad'],
            ['nombres' => 'Contador Test', 'password' => bcrypt('secret'), 'estado' => 1, 'idcaja' => 1, 'idalmacen' => 1]
        );
        $this->contabilidadUser->syncRoles(['CONTABILIDAD']);

        $this->administracionUser = User::firstOrCreate(
            ['user' => 'b7_administracion'],
            ['nombres' => 'Administrador Test', 'password' => bcrypt('secret'), 'estado' => 1, 'idcaja' => 1, 'idalmacen' => 1]
        );
        $this->administracionUser->syncRoles(['ADMINISTRACION']);

        $this->directorUser = User::firstOrCreate(
            ['user' => 'b7_director'],
            ['nombres' => 'Director General Test', 'password' => bcrypt('secret'), 'estado' => 1, 'idcaja' => 1, 'idalmacen' => 1]
        );
        $this->directorUser->syncRoles(['DIRECTOR_GENERAL']);

        $this->tesoreroUser = User::firstOrCreate(
            ['user' => 'b7_tesorero'],
            ['nombres' => 'Tesorero Test', 'password' => bcrypt('secret'), 'estado' => 1, 'idcaja' => 1, 'idalmacen' => 1]
        );
        $this->tesoreroUser->syncRoles(['TESORERO']);

        $this->unauthorizedUser = User::firstOrCreate(
            ['user' => 'b7_unauthorized'],
            ['nombres' => 'Sin Permisos Test', 'password' => bcrypt('secret'), 'estado' => 1, 'idcaja' => 1, 'idalmacen' => 1]
        );
        $this->unauthorizedUser->syncRoles([]);
        $this->unauthorizedUser->syncPermissions([]);

        // Setup Accounting Periods
        $this->openPeriod = AccountingPeriod::firstOrCreate(
            ['period_code' => '2026-07'],
            [
                'fiscal_year' => 2026,
                'month'       => 7,
                'start_date'  => '2026-07-01',
                'end_date'    => '2026-07-31',
                'status'      => 'OPEN',
            ]
        );
        $this->openPeriod->update(['status' => 'OPEN']);

        $this->closedPeriod = AccountingPeriod::firstOrCreate(
            ['period_code' => '2026-06'],
            [
                'fiscal_year' => 2026,
                'month'       => 6,
                'start_date'  => '2026-06-01',
                'end_date'    => '2026-06-30',
                'status'      => 'CLOSED',
            ]
        );
        $this->closedPeriod->update(['status' => 'CLOSED']);

        // Chart of Accounts
        $this->account10 = ChartOfAccount::firstOrCreate(
            ['code' => '10411B7'],
            [
                'name'            => 'Cuentas Corrientes B7',
                'element'         => 1,
                'nature'          => AccountNature::DEBIT,
                'account_type'    => AccountType::ACTIVO,
                'level'           => 5,
                'allows_movement' => true,
                'is_active'       => true,
            ]
        );

        $this->account60 = ChartOfAccount::firstOrCreate(
            ['code' => '60111B7'],
            [
                'name'            => 'Compras Mercaderías B7',
                'element'         => 6,
                'nature'          => AccountNature::DEBIT,
                'account_type'    => AccountType::GASTOS_NATURALEZA,
                'level'           => 5,
                'allows_movement' => true,
                'is_active'       => true,
            ]
        );

        $this->account42 = ChartOfAccount::firstOrCreate(
            ['code' => '42121B7'],
            [
                'name'            => 'Facturas Emitidas B7',
                'element'         => 4,
                'nature'          => AccountNature::CREDIT,
                'account_type'    => AccountType::PASIVO,
                'level'           => 5,
                'allows_movement' => true,
                'is_active'       => true,
            ]
        );
    }

    /**
     * Test 1: Spatie permissions default assignments and seeder idempotency
     */
    public function test_spatie_permissions_seeded_with_expected_default_assignments(): void
    {
        // 1. Check SuperAdmin has all permissions
        $this->assertTrue($this->superAdmin->can('accounting.view'));
        $this->assertTrue($this->superAdmin->can('accounting.post'));
        $this->assertTrue($this->superAdmin->can('accounting.close'));
        $this->assertTrue($this->superAdmin->can('accounting.reopen'));
        $this->assertTrue($this->superAdmin->can('accounting.export'));
        $this->assertTrue($this->superAdmin->can('treasury.manage'));
        $this->assertTrue($this->superAdmin->can('treasury.reconcile'));
        $this->assertTrue($this->superAdmin->can('budget.manage'));
        $this->assertTrue($this->superAdmin->can('budget.approve'));

        // 2. Check CONTABILIDAD (ACCOUNTING): view, post, close, export, reconcile
        $this->assertTrue($this->contabilidadUser->can('accounting.view'));
        $this->assertTrue($this->contabilidadUser->can('accounting.post'));
        $this->assertTrue($this->contabilidadUser->can('accounting.close'));
        $this->assertTrue($this->contabilidadUser->can('accounting.export'));
        $this->assertTrue($this->contabilidadUser->can('treasury.reconcile'));
        $this->assertFalse($this->contabilidadUser->can('budget.manage'));

        // 3. Check ADMINISTRACION: view, budget.*, treasury.manage
        $this->assertTrue($this->administracionUser->can('accounting.view'));
        $this->assertTrue($this->administracionUser->can('budget.manage'));
        $this->assertTrue($this->administracionUser->can('budget.approve'));
        $this->assertTrue($this->administracionUser->can('treasury.manage'));
        $this->assertFalse($this->administracionUser->can('accounting.post'));

        // 4. Check DIRECTOR_GENERAL (CEO): view, budget.approve, accounting.close
        $this->assertTrue($this->directorUser->can('accounting.view'));
        $this->assertTrue($this->directorUser->can('budget.approve'));
        $this->assertTrue($this->directorUser->can('accounting.close'));
        $this->assertFalse($this->directorUser->can('treasury.manage'));

        // 5. Check new TESORERO role (Open Decision): view, treasury.manage, treasury.reconcile
        $this->assertTrue($this->tesoreroUser->can('accounting.view'));
        $this->assertTrue($this->tesoreroUser->can('treasury.manage'));
        $this->assertTrue($this->tesoreroUser->can('treasury.reconcile'));
        $this->assertFalse($this->tesoreroUser->can('accounting.close'));
    }

    /**
     * Test 2: Idempotent permission/role seeder does not overwrite existing assignments
     */
    public function test_idempotent_seeder_does_not_overwrite_existing_assignments(): void
    {
        $customPerm = Permission::firstOrCreate(['name' => 'custom.audit.permission']);
        $contabilidadRole = Role::findByName('CONTABILIDAD');
        $contabilidadRole->givePermissionTo($customPerm);

        // Re-run seeder
        $this->seed(AccountingSecurityRoleSeeder::class);

        // Custom permission must still be assigned
        $this->assertTrue($contabilidadRole->fresh()->hasPermissionTo('custom.audit.permission'));
        $this->assertTrue($contabilidadRole->fresh()->hasPermissionTo('accounting.post'));
    }

    /**
     * Test 3: Acceptance: User without permission cannot view or execute actions
     */
    public function test_acceptance_user_without_permission_cannot_view_or_execute_actions(): void
    {
        // 1. User without permission cannot view general journal
        $this->actingAs($this->unauthorizedUser)
            ->get(route('accounting.journal.index'))
            ->assertForbidden();

        // 2. User without permission cannot view balance sheet
        $this->actingAs($this->unauthorizedUser)
            ->get(route('accounting.statements.balance_sheet'))
            ->assertForbidden();

        // 3. User without permission cannot export trial balance
        $this->actingAs($this->unauthorizedUser)
            ->get(route('accounting.trial_balance.pdf'))
            ->assertForbidden();

        // 4. User without treasury.manage cannot store bank accounts
        $this->actingAs($this->unauthorizedUser)
            ->post(route('treasury.bank_accounts.store'), [
                'account_number' => '123-456-789',
                'currency'       => 'PEN',
            ])
            ->assertForbidden();

        // 5. User without budget.manage cannot create budgets
        $this->actingAs($this->unauthorizedUser)
            ->post(route('accounting.budgets.store'), [
                'fiscal_year' => 2026,
            ])
            ->assertForbidden();

        // 6. User without accounting.close cannot close periods
        $this->actingAs($this->unauthorizedUser)
            ->post(route('accounting.period_closing.close', $this->openPeriod->id), [
                'notes' => 'Intento no autorizado de cierre',
            ])
            ->assertForbidden();
    }

    /**
     * Test 4: Middleware CheckAccountingPeriodOpen blocks writes to closed periods (Controlled Error)
     */
    public function test_writing_to_closed_period_returns_controlled_error(): void
    {
        // Test bank transfer into a closed period (June 2026)
        $fundSource = FundSource::firstOrCreate(['code' => 'FTE-B7-SRC'], [
            'name'            => 'Recursos B7 Origen',
            'bank_name'       => 'Banco de la Nación',
            'account_number'  => '999-001-B7',
            'currency'        => 'PEN',
            'initial_balance' => 50000.00,
            'current_balance' => 50000.00,
            'is_cut'          => false,
            'is_active'       => true,
        ]);

        $fundTarget = FundSource::firstOrCreate(['code' => 'FTE-B7-TGT'], [
            'name'            => 'Recursos B7 Destino',
            'bank_name'       => 'Banco de la Nación',
            'account_number'  => '999-002-B7',
            'currency'        => 'PEN',
            'initial_balance' => 10000.00,
            'current_balance' => 10000.00,
            'is_cut'          => false,
            'is_active'       => true,
        ]);

        $accSource = BankAccount::create([
            'fund_source_id'        => $fundSource->id,
            'account_number'        => '999-001-' . uniqid(),
            'bank_name'             => 'Banco de la Nación',
            'account_type'          => 'CURRENT',
            'currency'              => 'PEN',
            'accounting_account_id' => $this->account10->id,
            'initial_balance'       => 50000.00,
            'current_balance'       => 50000.00,
            'is_active'             => true,
        ]);

        $accTarget = BankAccount::create([
            'fund_source_id'        => $fundTarget->id,
            'account_number'        => '999-002-' . uniqid(),
            'bank_name'             => 'Banco de la Nación',
            'account_type'          => 'SAVINGS',
            'currency'              => 'PEN',
            'accounting_account_id' => $this->account10->id,
            'initial_balance'       => 10000.00,
            'current_balance'       => 10000.00,
            'is_active'             => true,
        ]);

        // Attempt transfer dated within CLOSED period (2026-06-15) with User having treasury.manage but NOT accounting.reopen
        $response = $this->actingAs($this->administracionUser)
            ->postJson(route('treasury.transfers.bank_to_bank'), [
                'source_bank_account_id'      => $accSource->id,
                'destination_bank_account_id' => $accTarget->id,
                'amount'                      => 1000.00,
                'transfer_date'               => '2026-06-15', // Closed period
                'concept'                     => 'Transferencia de prueba periodo cerrado',
                'reference_number'            => 'TRF-CLOSED-TEST',
            ]);

        // Assert Controlled Error HTTP 403 Forbidden with PERIOD_CLOSED
        $response->assertStatus(403);
        $response->assertJson([
            'success'     => false,
            'error'       => 'PERIOD_CLOSED',
            'period_code' => '2026-06',
        ]);
        $this->assertStringContainsString('cerrado', $response->json('message'));
    }

    /**
     * Test 5: Middleware CheckAccountingPeriodOpen allows write when user has accounting.reopen permission
     */
    public function test_writing_to_closed_period_allowed_with_accounting_reopen_permission(): void
    {
        // Grant accounting.reopen to contabilidadUser
        $this->contabilidadUser->givePermissionTo('accounting.reopen');

        $fundSource = FundSource::firstOrCreate(['code' => 'FTE-B7-SRC2'], [
            'name'            => 'Recursos B7 Origen 2',
            'bank_name'       => 'Banco de la Nación',
            'account_number'  => '999-003-B7',
            'currency'        => 'PEN',
            'initial_balance' => 50000.00,
            'current_balance' => 50000.00,
            'is_cut'          => false,
            'is_active'       => true,
        ]);

        $fundTarget = FundSource::firstOrCreate(['code' => 'FTE-B7-TGT2'], [
            'name'            => 'Recursos B7 Destino 2',
            'bank_name'       => 'Banco de la Nación',
            'account_number'  => '999-004-B7',
            'currency'        => 'PEN',
            'initial_balance' => 10000.00,
            'current_balance' => 10000.00,
            'is_cut'          => false,
            'is_active'       => true,
        ]);

        $accSource = BankAccount::create([
            'fund_source_id'        => $fundSource->id,
            'account_number'        => '999-003-' . uniqid(),
            'bank_name'             => 'Banco de la Nación',
            'account_type'          => 'CURRENT',
            'currency'              => 'PEN',
            'accounting_account_id' => $this->account10->id,
            'initial_balance'       => 50000.00,
            'current_balance'       => 50000.00,
            'is_active'             => true,
        ]);

        $accTarget = BankAccount::create([
            'fund_source_id'        => $fundTarget->id,
            'account_number'        => '999-004-' . uniqid(),
            'bank_name'             => 'Banco de la Nación',
            'account_type'          => 'SAVINGS',
            'currency'              => 'PEN',
            'accounting_account_id' => $this->account10->id,
            'initial_balance'       => 10000.00,
            'current_balance'       => 10000.00,
            'is_active'             => true,
        ]);

        // User also needs treasury.manage to pass the ability gate
        $this->contabilidadUser->givePermissionTo('treasury.manage');

        $response = $this->actingAs($this->contabilidadUser)
            ->postJson(route('treasury.transfers.bank_to_bank'), [
                'source_bank_account_id'      => $accSource->id,
                'destination_bank_account_id' => $accTarget->id,
                'amount'                      => 1000.00,
                'transfer_date'               => '2026-06-15', // Closed period
                'concept'                     => 'Transferencia permitida con reapertura',
                'reference_number'            => 'TRF-ALLOWED-TEST',
            ]);

        // Must not be blocked by the period-closed middleware
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * Test 6: Audit trail logs user, IP, date for posting, closing and reopening actions
     */
    public function test_audit_trail_logs_user_ip_and_date_for_posting_closing_and_reopening(): void
    {
        // 1. Audit trail for posting a journal entry
        $this->actingAs($this->superAdmin);
        $entry = $this->accountingService->createJournalEntry([
            'accounting_period_id' => $this->openPeriod->id,
            'entry_number'         => 'DIARIO-B7-AUDIT-' . uniqid(),
            'entry_date'           => '2026-07-10',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Asiento de prueba para auditoria B7',
            'currency'             => 'PEN',
            'created_by_user_id'   => $this->superAdmin->id,
            'status'               => JournalStatus::POSTED,
        ], [
            ['account_id' => $this->account60->id, 'debit' => 2500.00, 'credit' => 0.00],
            ['account_id' => $this->account42->id, 'debit' => 0.00, 'credit' => 2500.00],
        ]);

        $postAudit = AccountingAuditLog::where('action', 'POST_ENTRY')
            ->where('auditable_id', $entry->id)
            ->first();

        $this->assertNotNull($postAudit);
        $this->assertEquals($this->superAdmin->id, $postAudit->user_id);
        $this->assertNotNull($postAudit->ip_address);
        $this->assertNotNull($postAudit->created_at);

        // 2. Audit trail for closing a period
        $periodToClose = AccountingPeriod::firstOrCreate(
            ['period_code' => '2026-08'],
            [
                'fiscal_year' => 2026,
                'month'       => 8,
                'start_date'  => '2026-08-01',
                'end_date'    => '2026-08-31',
                'status'      => 'OPEN',
            ]
        );
        $periodToClose->update(['status' => 'OPEN']);

        $this->closingService->closeMonthlyPeriod($periodToClose, $this->superAdmin->id, 'Cierre auditado B7');

        $closeAudit = AccountingAuditLog::where('action', 'CLOSE_PERIOD')
            ->where('period_code', '2026-08')
            ->first();

        $this->assertNotNull($closeAudit);
        $this->assertEquals($this->superAdmin->id, $closeAudit->user_id);
        $this->assertNotNull($closeAudit->ip_address);
        $this->assertStringContainsString('Cierre auditado B7', $closeAudit->description);

        // 3. Audit trail for reopening a period
        $this->closingService->reopenPeriod($periodToClose, $this->superAdmin->id, 'Reapertura para regularizacion');

        $reopenAudit = AccountingAuditLog::where('action', 'REOPEN_PERIOD')
            ->where('period_code', '2026-08')
            ->first();

        $this->assertNotNull($reopenAudit);
        $this->assertEquals($this->superAdmin->id, $reopenAudit->user_id);
        $this->assertNotNull($reopenAudit->ip_address);
        $this->assertStringContainsString('Reapertura para regularizacion', $reopenAudit->description);
    }

    /**
     * Test 7: Acceptance: Artisan accounting:check-integrity reports clean status on test data
     */
    public function test_artisan_accounting_check_integrity_reports_clean_status(): void
    {
        // Populate valid balanced entry in the database
        $this->accountingService->createJournalEntry([
            'accounting_period_id' => $this->openPeriod->id,
            'entry_number'         => 'DIARIO-CLEAN-' . uniqid(),
            'entry_date'           => '2026-07-20',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Asiento completamente cuadrado para check-integrity',
            'currency'             => 'PEN',
            'status'               => JournalStatus::POSTED,
        ], [
            ['account_id' => $this->account60->id, 'debit' => 1200.00, 'credit' => 0.00],
            ['account_id' => $this->account42->id, 'debit' => 0.00, 'credit' => 1200.00],
        ]);

        // Run check-integrity command
        $this->artisan('accounting:check-integrity')
            ->expectsOutputToContain('LIMPIO / CLEAN')
            ->expectsOutputToContain('[OK] INTEGRIDAD CONTABLE VERIFICADA CON ÉXITO')
            ->assertExitCode(0);
    }

    /**
     * Test 8: Artisan accounting:check-integrity detects discrepancies if database is compromised
     */
    public function test_artisan_accounting_check_integrity_detects_unbalanced_entries(): void
    {
        // Deliberately create an entry with header total matching, but lines discrepancy
        $unbalancedEntry = JournalEntry::create([
            'uuid'                 => (string) \Illuminate\Support\Str::uuid(),
            'accounting_period_id' => $this->openPeriod->id,
            'entry_number'         => 'DESCUADRADO-' . uniqid(),
            'entry_date'           => '2026-07-25',
            'entry_type'           => VoucherType::OPERATING,
            'concept'              => 'Asiento intencionalmente descuadrado',
            'currency'             => 'PEN',
            'total_debit'          => 1000.00,
            'total_credit'         => 1000.00, // Satisfies database table check constraint
            'status'               => JournalStatus::POSTED,
            'created_by_user_id'   => $this->superAdmin->id,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $unbalancedEntry->id,
            'line_number'      => 1,
            'account_id'       => $this->account60->id,
            'debit'            => 1000.00,
            'credit'           => 0.00,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $unbalancedEntry->id,
            'line_number'      => 2,
            'account_id'       => $this->account42->id,
            'debit'            => 0.00,
            'credit'           => 800.00, // Line discrepancy: 1000 debit != 800 credit
        ]);

        // Run check-integrity command, expecting failure
        $this->artisan('accounting:check-integrity')
            ->expectsOutputToContain('DESCUADRADO')
            ->assertExitCode(1);

        // Cleanup the corrupt entry so other tests aren't affected
        $unbalancedEntry->lines()->delete();
        $unbalancedEntry->update(['status' => JournalStatus::DRAFT]);
        $unbalancedEntry->delete();
    }

    /**
     * Test 9: Artisan accounting:close-period closes target period cleanly
     */
    public function test_artisan_accounting_close_period_command(): void
    {
        $periodCode = '2026-11';
        $period = AccountingPeriod::firstOrCreate(
            ['period_code' => $periodCode],
            [
                'fiscal_year' => 2026,
                'month'       => 11,
                'start_date'  => '2026-11-01',
                'end_date'    => '2026-11-30',
                'status'      => 'OPEN',
            ]
        );
        $period->update(['status' => 'OPEN']);

        $this->artisan('accounting:close-period', [
            'period_code' => $periodCode,
            '--force'     => true,
            '--notes'     => 'Cierre mediante comando B7',
        ])
        ->expectsOutputToContain("Período {$periodCode} cerrado con éxito (CLOSED)")
        ->assertSuccessful();

        $this->assertEquals('CLOSED', $period->fresh()->status);
    }

    /**
     * Test 10: Sidebar menu renders badges for open periods and pending reconciliations
     */
    public function test_sidebar_badges_render_open_periods_count_and_pending_reconciliations(): void
    {
        // 1. Ensure at least one open period exists
        $this->openPeriod->update(['status' => 'OPEN']);

        // 2. Ensure at least one pending reconciliation exists
        $fund = FundSource::firstOrCreate(['code' => 'FTE-B7-3'], [
            'name'            => 'Recursos B7-3',
            'bank_name'       => 'Banco de la Nación',
            'account_number'  => '999-003-B7',
            'currency'        => 'PEN',
            'initial_balance' => 25000.00,
            'current_balance' => 25000.00,
            'is_cut'          => false,
            'is_active'       => true,
        ]);

        $bankAccount = BankAccount::create([
            'fund_source_id'        => $fund->id,
            'account_number'        => '999-005-' . uniqid(),
            'bank_name'             => 'Banco de la Nación',
            'account_type'          => 'CURRENT',
            'currency'              => 'PEN',
            'accounting_account_id' => $this->account10->id,
            'initial_balance'       => 25000.00,
            'current_balance'       => 25000.00,
            'is_active'             => true,
        ]);

        RdrBankReconciliation::create([
            'fund_source_id'            => $fund->id,
            'bank_account_id'           => $bankAccount->id,
            'accounting_period_id'      => $this->openPeriod->id,
            'period_year'               => 2026,
            'period_month'              => 7,
            'statement_closing_date'    => '2026-07-31',
            'bank_statement_balance'    => 25000.00,
            'system_calculated_balance' => 25000.00,
            'book_calculated_balance'   => 25000.00,
            'status'                    => 'PENDIENTE',
            'reconciled_by_user_id'     => $this->superAdmin->id,
        ]);

        // Request an accounting index view
        $response = $this->actingAs($this->superAdmin)
            ->get(route('accounting.journal.index'));

        $response->assertOk();
        // Check for badge in rendered HTML
        $response->assertSee('Abierto', false);
        $response->assertSee('Conciliación Bancaria', false);
    }
}
