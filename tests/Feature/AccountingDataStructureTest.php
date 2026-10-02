<?php

namespace Tests\Feature;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\AccountingRule;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\AccountingService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AccountingDataStructureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected AccountingService $accountingService;
    protected AccountingPeriod $period;
    protected ChartOfAccount $accountCash;
    protected ChartOfAccount $accountSales;
    protected ChartOfAccount $accountIgv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = app(AccountingService::class);

        // Find or create test user
        $this->user = User::where('user', 'admin')->first() ?? User::firstOrCreate(
            ['user' => 'acct_tester'],
            [
                'nombres'  => 'Contador Tester',
                'password' => bcrypt('password123'),
                'estado'   => 1,
            ]
        );

        // Open current period
        $today = now();
        $this->period = AccountingPeriod::firstOrCreate(
            ['period_code' => $today->format('Y-m')],
            [
                'fiscal_year' => $today->year,
                'month'       => $today->month,
                'start_date'  => $today->copy()->startOfMonth()->toDateString(),
                'end_date'    => $today->copy()->endOfMonth()->toDateString(),
                'status'      => 'OPEN',
            ]
        );
        $this->period->update(['status' => 'OPEN']);

        // Seed or find core test accounts
        $this->accountCash = ChartOfAccount::firstOrCreate(
            ['code' => '1011'],
            [
                'name'              => 'Caja Central Test',
                'element'           => 1,
                'level'             => 4,
                'nature'            => AccountNature::DEBIT,
                'classification'    => AccountType::ACTIVO,
                'accepts_movements' => true,
                'allows_movement'   => true,
                'active'            => true,
                'is_active'         => true,
            ]
        );

        $this->accountSales = ChartOfAccount::firstOrCreate(
            ['code' => '70111'],
            [
                'name'              => 'Ventas Test',
                'element'           => 7,
                'level'             => 5,
                'nature'            => AccountNature::CREDIT,
                'classification'    => AccountType::INGRESOS,
                'accepts_movements' => true,
                'allows_movement'   => true,
                'active'            => true,
                'is_active'         => true,
            ]
        );

        $this->accountIgv = ChartOfAccount::firstOrCreate(
            ['code' => '40111'],
            [
                'name'              => 'IGV Cuenta Propia Test',
                'element'           => 4,
                'level'             => 5,
                'nature'            => AccountNature::CREDIT,
                'classification'    => AccountType::PASIVO,
                'accepts_movements' => true,
                'allows_movement'   => true,
                'active'            => true,
                'is_active'         => true,
            ]
        );
    }

    /**
     * Test Chart of Accounts hierarchy, parent-child relations, and movement restriction.
     */
    public function test_chart_of_accounts_hierarchy_and_movement_restrictions(): void
    {
        // Summary parent account (Level 2: 10 Efectivo)
        $parent = ChartOfAccount::firstOrCreate(
            ['code' => '10_TEST'],
            [
                'name'              => 'Efectivo y Equivalentes Test',
                'element'           => 1,
                'level'             => 2,
                'nature'            => AccountNature::DEBIT,
                'classification'    => AccountType::ACTIVO,
                'accepts_movements' => false,
                'allows_movement'   => false,
                'active'            => true,
            ]
        );

        // Imputable child account (Level 3: 101 Caja)
        $child = ChartOfAccount::firstOrCreate(
            ['code' => '101_TEST'],
            [
                'name'              => 'Caja Hija Test',
                'element'           => 1,
                'level'             => 3,
                'nature'            => AccountNature::DEBIT,
                'classification'    => AccountType::ACTIVO,
                'accepts_movements' => true,
                'allows_movement'   => true,
                'parent_id'         => $parent->id,
                'active'            => true,
            ]
        );

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($child->accepts_movements);
        $this->assertFalse($parent->accepts_movements);

        // Attempting to post to summary parent account must fail in service layer
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/does not accept direct movements/');

        $this->accountingService->createJournalEntry([
            'accounting_period_id' => $this->period->id,
            'concept'              => 'Test Movimiento Inválido en Cuenta Resumen',
            'created_by_user_id'   => $this->user->id,
        ], [
            ['account_id' => $parent->id, 'debit' => 100.00, 'credit' => 0.00],
            ['account_id' => $this->accountSales->id, 'debit' => 0.00, 'credit' => 100.00],
        ]);
    }

    /**
     * Test creating a balanced journal entry succeeds with full double-entry persistence.
     */
    public function test_creating_balanced_journal_entry_succeeds_and_computes_totals(): void
    {
        $header = [
            'accounting_period_id' => $this->period->id,
            'entry_date'           => now()->toDateString(),
            'concept'              => 'Venta en efectivo en mostrador POS',
            'entry_type'           => VoucherType::OPERATING,
            'currency'             => 'PEN',
            'exchange_rate'        => 3.7500,
            'created_by_user_id'   => $this->user->id,
            'source_type'          => 'App\Models\Billing',
            'source_id'            => 9999,
            'event_key'            => 'sale_pos',
        ];

        // Total Debit: 118.00 (Cash)
        // Total Credit: 18.00 (IGV) + 100.00 (Sales) = 118.00
        $lines = [
            ['account_id' => $this->accountCash->id, 'debit' => 118.00, 'credit' => 0.00, 'glosa' => 'Cobranza contado'],
            ['account_id' => $this->accountIgv->id,  'debit' => 0.00,   'credit' => 18.00,  'glosa' => 'IGV Débito fiscal'],
            ['account_id' => $this->accountSales->id,'debit' => 0.00,   'credit' => 100.00, 'glosa' => 'Ingreso por venta'],
        ];

        $entry = $this->accountingService->createJournalEntry($header, $lines);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertEquals(JournalStatus::POSTED, $entry->status);
        $this->assertEquals(118.00, (float) $entry->total_debit);
        $this->assertEquals(118.00, (float) $entry->total_credit);
        $this->assertCount(3, $entry->lines);
        $this->assertNotNull($entry->entry_number);
        $this->assertNotNull($entry->posted_at);

        // Check foreign exchange translation
        $cashLine = $entry->lines->firstWhere('account_id', $this->accountCash->id);
        $this->assertEquals(round(118.00 / 3.75, 4), (float) $cashLine->debit_usd);
    }

    /**
     * Test integrity constraint: Unbalanced journal entry is rejected in the service layer.
     */
    public function test_unbalanced_journal_entry_is_rejected_in_service_layer(): void
    {
        $header = [
            'accounting_period_id' => $this->period->id,
            'concept'              => 'Asiento descuadrado no permitido',
            'created_by_user_id'   => $this->user->id,
        ];

        // Debit: 100.00, Credit: 80.00 (Mismatch of 20.00)
        $lines = [
            ['account_id' => $this->accountCash->id, 'debit' => 100.00, 'credit' => 0.00],
            ['account_id' => $this->accountSales->id,'debit' => 0.00,   'credit' => 80.00],
        ];

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Unbalanced journal entry/');

        $this->accountingService->createJournalEntry($header, $lines);
    }

    /**
     * Test integrity constraint: Unbalanced total_debit != total_credit is rejected by DB CHECK constraint.
     */
    public function test_unbalanced_entry_is_rejected_by_database_check_constraint(): void
    {
        $this->expectException(QueryException::class);

        // Attempt direct insertion into journal_entries where total_debit != total_credit
        JournalEntry::create([
            'uuid'                 => (string) \Illuminate\Support\Str::uuid(),
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'TEST-CHK-01',
            'entry_date'           => now()->toDateString(),
            'concept'              => 'Direct unbalanced insert',
            'total_debit'          => 200.00,
            'total_credit'         => 150.00, // DB check constraint chk_journal_entry_balanced will fail
            'created_by_user_id'   => $this->user->id,
        ]);
    }

    /**
     * Test immutability: Posted journal entries cannot be edited or deleted.
     */
    public function test_posted_journal_entries_are_strictly_immutable(): void
    {
        $entry = $this->accountingService->createJournalEntry([
            'accounting_period_id' => $this->period->id,
            'concept'              => 'Asiento inmutable de prueba',
            'created_by_user_id'   => $this->user->id,
        ], [
            ['account_id' => $this->accountCash->id, 'debit' => 50.00, 'credit' => 0.00],
            ['account_id' => $this->accountSales->id,'debit' => 0.00,  'credit' => 50.00],
        ]);

        $this->assertTrue($entry->isPosted());

        // 1. Attempting to update concept on posted entry must throw DomainException
        try {
            $entry->update(['concept' => 'Concepto Modificado Ilegal']);
            $this->fail('Expected DomainException was not thrown on update of posted entry.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        // Verify concept was not updated
        $this->assertEquals('Asiento inmutable de prueba', $entry->fresh()->concept);

        // 2. Attempting to delete posted entry must throw DomainException
        try {
            $entry->delete();
            $this->fail('Expected DomainException was not thrown on deletion of posted entry.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }

        // Verify entry still exists in DB
        $this->assertDatabaseHas('journal_entries', ['id' => $entry->id]);

        // 3. Attempting to update or delete detail lines must also fail
        $line = $entry->lines->first();
        try {
            $line->update(['debit' => 999.00]);
            $this->fail('Expected DomainException was not thrown on update of line.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('immutable', $e->getMessage());
        }
    }

    /**
     * Test reversing a posted journal entry creates an offsetting reversal entry.
     */
    public function test_reversing_posted_journal_entry_creates_offsetting_reversal_entry(): void
    {
        $originalEntry = $this->accountingService->createJournalEntry([
            'accounting_period_id' => $this->period->id,
            'concept'              => 'Cobro a anular',
            'created_by_user_id'   => $this->user->id,
        ], [
            ['account_id' => $this->accountCash->id, 'debit' => 250.00, 'credit' => 0.00],
            ['account_id' => $this->accountSales->id,'debit' => 0.00,   'credit' => 250.00],
        ]);

        $reversalEntry = $this->accountingService->reverseJournalEntry($originalEntry, $this->user);

        // Original entry must be marked REVERSED and linked
        $freshOriginal = $originalEntry->fresh();
        $this->assertEquals(JournalStatus::REVERSED, $freshOriginal->status);
        $this->assertEquals($reversalEntry->id, $freshOriginal->reversed_by_entry_id);

        // Reversal entry must have swapped lines
        $this->assertEquals(VoucherType::REVERSAL, $reversalEntry->entry_type);
        $this->assertEquals($originalEntry->id, $reversalEntry->reverses_entry_id);
        $this->assertEquals(250.00, (float) $reversalEntry->total_debit);
        $this->assertEquals(250.00, (float) $reversalEntry->total_credit);

        // Cash was debit 250 in original, so in reversal Cash must be credit 250
        $revCashLine = $reversalEntry->lines->firstWhere('account_id', $this->accountCash->id);
        $this->assertEquals(0.00, (float) $revCashLine->debit);
        $this->assertEquals(250.00, (float) $revCashLine->credit);
    }

    /**
     * Test idempotency unique composite index (source_type, source_id, event_key).
     */
    public function test_idempotency_prevents_duplicate_entries(): void
    {
        $header = [
            'accounting_period_id' => $this->period->id,
            'concept'              => 'Idempotent Billing Entry',
            'source_type'          => 'App\Models\Billing',
            'source_id'            => 8888,
            'event_key'            => 'invoice_posted',
            'created_by_user_id'   => $this->user->id,
        ];

        $lines = [
            ['account_id' => $this->accountCash->id, 'debit' => 100.00, 'credit' => 0.00],
            ['account_id' => $this->accountSales->id,'debit' => 0.00,   'credit' => 100.00],
        ];

        // 1. First execution creates entry
        $entry1 = $this->accountingService->createJournalEntry($header, $lines);

        // 2. Second execution returns same entry without duplicating
        $entry2 = $this->accountingService->createJournalEntry($header, $lines);

        $this->assertEquals($entry1->id, $entry2->id);
        $this->assertEquals(1, JournalEntry::where('source_type', 'App\Models\Billing')->where('source_id', 8888)->count());

        // 3. Direct DB insert with identical (source_type, source_id, event_key) must trigger DB unique error
        $this->expectException(QueryException::class);

        JournalEntry::create([
            'uuid'                 => (string) \Illuminate\Support\Str::uuid(),
            'accounting_period_id' => $this->period->id,
            'entry_number'         => 'TEST-IDEMP-02',
            'entry_date'           => now()->toDateString(),
            'concept'              => 'Duplicate insert',
            'source_type'          => 'App\Models\Billing',
            'source_id'            => 8888,
            'event_key'            => 'invoice_posted', // Collides with unique index uk_journal_source_event
            'total_debit'          => 100.00,
            'total_credit'         => 100.00,
            'created_by_user_id'   => $this->user->id,
        ]);
    }

    /**
     * Test period closing blocks postings in closed or locked periods.
     */
    public function test_period_closing_blocks_postings(): void
    {
        $closedPeriod = AccountingPeriod::create([
            'fiscal_year' => 2025,
            'month'       => 12,
            'period_code' => '2025-12',
            'start_date'  => '2025-12-01',
            'end_date'    => '2025-12-31',
            'status'      => 'LOCKED',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/is LOCKED. Postings are strictly prohibited/');

        $this->accountingService->createJournalEntry([
            'accounting_period_id' => $closedPeriod->id,
            'concept'              => 'Operación en período cerrado',
            'created_by_user_id'   => $this->user->id,
        ], [
            ['account_id' => $this->accountCash->id, 'debit' => 100.00, 'credit' => 0.00],
            ['account_id' => $this->accountSales->id,'debit' => 0.00,   'credit' => 100.00],
        ]);
    }

    /**
     * Test Chart of Accounts CRUD endpoints.
     */
    public function test_chart_of_accounts_crud_endpoints(): void
    {
        $this->actingAs($this->user);

        // 1. Index view returns 200
        $resIndex = $this->get(route('accounting.chart_of_accounts.index'));
        $resIndex->assertOk();

        // 2. DataTables endpoint returns JSON data
        $resData = $this->getJson(route('accounting.chart_of_accounts.data'));
        $resData->assertOk();
        $this->assertArrayHasKey('data', $resData->json());

        // 3. Tree endpoint returns hierarchical JSON
        $resTree = $this->getJson(route('accounting.chart_of_accounts.tree'));
        $resTree->assertOk();
        $this->assertTrue($resTree->json('success'));
        $this->assertNotEmpty($resTree->json('tree'));

        // 4. Create new account
        $resStore = $this->postJson(route('accounting.chart_of_accounts.store'), [
            'code'              => '10419',
            'name'              => 'Banco Interbank Operaciones',
            'element'           => 1,
            'level'             => 5,
            'nature'            => 'DEBIT',
            'classification'    => 'ACTIVO',
            'accepts_movements' => 1,
            'active'            => 1,
        ]);
        $resStore->assertStatus(201);
        $createdId = $resStore->json('account.id');

        // 5. Update account
        $resUpdate = $this->putJson(route('accounting.chart_of_accounts.update', $createdId), [
            'code'              => '10419',
            'name'              => 'Banco Interbank Modificado',
            'element'           => 1,
            'level'             => 5,
            'nature'            => 'DEBIT',
            'classification'    => 'ACTIVO',
            'accepts_movements' => 1,
            'active'            => 1,
        ]);
        $resUpdate->assertOk();
        $this->assertEquals('Banco Interbank Modificado', $resUpdate->json('account.name'));

        // 6. Delete account
        $resDelete = $this->deleteJson(route('accounting.chart_of_accounts.destroy', $createdId));
        $resDelete->assertOk();
        $this->assertTrue($resDelete->json('success'));

        // Verify soft delete
        $this->assertSoftDeleted('chart_of_accounts', ['id' => $createdId]);
    }
}
