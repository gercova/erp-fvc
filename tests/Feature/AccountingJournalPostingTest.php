<?php

namespace Tests\Feature;

use App\Enums\JournalStatus;
use App\Enums\VoucherType;
use App\Events\BillingVoided;
use App\Events\CreditNoteIssued;
use App\Events\PaymentReceived;
use App\Events\PurchaseRecorded;
use App\Events\SaleCompleted;
use App\Models\AccountingPeriod;
use App\Models\AccountingPostingFailure;
use App\Models\ActivityTransaction;
use App\Models\Billing;
use App\Models\Buy;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\JournalEntry;
use App\Models\Provider;
use App\Models\TypeDocument;
use App\Models\User;
use App\Services\Accounting\AccountingEventMapper;
use App\Services\Accounting\JournalPostingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AccountingJournalPostingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected JournalPostingService $postingService;
    protected AccountingEventMapper $mapper;
    protected AccountingPeriod $openPeriod;
    protected Client $client;
    protected Provider $provider;
    protected TypeDocument $typeFactura;
    protected TypeDocument $typeBoleta;
    protected TypeDocument $typeCreditNote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postingService = app(JournalPostingService::class);
        $this->mapper = app(AccountingEventMapper::class);

        // Find or create test user
        $this->user = User::where('user', 'admin')->first() ?? User::firstOrCreate(
            ['user' => 'acct_poster'],
            [
                'nombres'  => 'Contador Tester',
                'password' => bcrypt('password123'),
                'estado'   => 1,
            ]
        );

        // Setup current open period
        $today = now();
        $this->openPeriod = AccountingPeriod::firstOrCreate(
            ['period_code' => $today->format('Y-m')],
            [
                'fiscal_year' => $today->year,
                'month'       => $today->month,
                'start_date'  => $today->copy()->startOfMonth()->toDateString(),
                'end_date'    => $today->copy()->endOfMonth()->toDateString(),
                'status'      => 'OPEN',
            ]
        );
        $this->openPeriod->update(['status' => 'OPEN']);

        // Test Client & Provider
        $this->client = Client::first() ?? Client::create([
            'iddoc'         => 4,
            'nro_documento' => '20123456789',
            'nombres'       => 'CLIENTE PRUEBA CONTABILIDAD SAC',
            'direccion'     => 'Av. Universitaria 123',
            'codigo_pais'   => 'PE',
            'ubigeo'        => '220901',
            'telefono'      => '999888777',
            'email'         => 'cliente@example.com',
        ]);

        $this->provider = Provider::first() ?? Provider::create([
            'iddoc'         => 4,
            'nro_documento' => '20987654321',
            'nombres'       => 'PROVEEDOR CENTRAL SAC',
            'direccion'     => 'Jr. Comercio 456',
            'codigo_pais'   => 'PE',
            'ubigeo'        => '220901',
            'telefono'      => '999111222',
            'email'         => 'proveedor@example.com',
        ]);

        $this->typeFactura = TypeDocument::firstOrCreate(['codigo' => '01'], ['descripcion' => 'FACTURA ELECTRÓNICA']);
        $this->typeBoleta = TypeDocument::firstOrCreate(['codigo' => '03'], ['descripcion' => 'BOLETA DE VENTA ELECTRÓNICA']);
        $this->typeCreditNote = TypeDocument::firstOrCreate(['codigo' => '07'], ['descripcion' => 'NOTA DE CRÉDITO']);
    }

    protected function createBilling(array $attributes = []): Billing
    {
        return Billing::create(array_merge([
            'idtipo_comprobante' => $this->typeFactura->id,
            'serie'              => 'F001',
            'correlativo'        => (string) rand(100000, 999999),
            'fecha_emision'      => now()->toDateString(),
            'fecha_vencimiento'  => now()->addDays(30)->toDateString(),
            'hora'               => '12:00:00',
            'idcliente'          => $this->client->id,
            'idmoneda'           => 1,
            'idpago'             => 1,
            'modo_pago'          => 1,
            'gravada'            => 1000.00,
            'exonerada'          => 0.00,
            'inafecta'           => 0.00,
            'gratuita'           => 0.00,
            'igv'                => 180.00,
            'total'              => 1180.00,
            'idusuario'          => $this->user->id,
            'idalmacen'          => 1,
        ], $attributes));
    }

    protected function createBuy(array $attributes = []): Buy
    {
        return Buy::create(array_merge([
            'idtipo_comprobante' => $this->typeFactura->id,
            'serie'              => 'F100',
            'correlativo'        => (string) rand(100000, 999999),
            'fecha_emision'      => now()->toDateString(),
            'fecha_vencimiento'  => now()->addDays(30)->toDateString(),
            'hora'               => '12:00:00',
            'idproveedor'        => $this->provider->id,
            'idalmacen'          => 1,
            'idmoneda'           => 1,
            'idpago'             => 1,
            'modo_pago'          => 1,
            'condicion_pago'     => 'CREDITO',
            'gravada'            => 1000.00,
            'exonerada'          => 0.00,
            'inafecta'           => 0.00,
            'gratuita'           => 0.00,
            'anticipo'           => 0.00,
            'otros_cargos'       => 0.00,
            'igv'                => 180.00,
            'total'              => 1180.00,
            'idusuario'          => $this->user->id,
        ], $attributes));
    }

    protected function createActivityTransaction(array $attributes = []): ActivityTransaction
    {
        return ActivityTransaction::create(array_merge([
            'productive_activity_id' => 1,
            'productive_unit_id'     => null,
            'category_id'            => 1,
            'fund_source_id'         => 1,
            'concept'                => 'Transacción APE prueba',
            'period_month'           => (int) date('m'),
            'period_year'            => (int) date('Y'),
            'transaction_type'       => 'INCOME',
            'amount'                 => 1000.00,
            'transaction_date'       => now()->toDateString(),
            'registered_by_user_id'  => $this->user->id,
        ], $attributes));
    }

    /**
     * 1. Test Taxable Sale (Gravada + IGV).
     */
    public function test_taxable_sale_creates_balanced_journal_entry(): void
    {
        $billing = $this->createBilling([
            'gravada'   => 1000.00,
            'exonerada' => 0.00,
            'inafecta'  => 0.00,
            'gratuita'  => 0.00,
            'igv'       => 180.00,
            'total'     => 1180.00,
        ]);

        $entry = $this->postingService->post($billing);

        $this->assertNotNull($entry);
        $this->assertEquals(JournalStatus::POSTED, $entry->status);
        $this->assertEquals(1180.00, (float) $entry->total_debit);
        $this->assertEquals(1180.00, (float) $entry->total_credit);
        $this->assertEquals($entry->total_debit, $entry->total_credit);

        // Lines check: 1212 debit 1180, 40111 credit 180, 70111 credit 1000
        $debitLine = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '1212'))->first();
        $igvLine   = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '40111'))->first();
        $revLine   = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '70111'))->first();

        $this->assertNotNull($debitLine);
        $this->assertEquals(1180.00, (float) $debitLine->debit);
        $this->assertNotNull($igvLine);
        $this->assertEquals(180.00, (float) $igvLine->credit);
        $this->assertNotNull($revLine);
        $this->assertEquals(1000.00, (float) $revLine->credit);
    }

    /**
     * 2. Test Exempt Sale (Exonerada without IGV).
     */
    public function test_exempt_sale_creates_balanced_journal_entry_without_igv(): void
    {
        $billing = $this->createBilling([
            'idtipo_comprobante' => $this->typeBoleta->id,
            'serie'              => 'B001',
            'gravada'            => 0.00,
            'exonerada'          => 450.00,
            'inafecta'           => 0.00,
            'gratuita'           => 0.00,
            'igv'                => 0.00,
            'total'              => 450.00,
        ]);

        $entry = $this->postingService->post($billing);

        $this->assertNotNull($entry);
        $this->assertEquals(450.00, (float) $entry->total_debit);
        $this->assertEquals(450.00, (float) $entry->total_credit);

        // No IGV line should be created
        $igvLine = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '40111'))->first();
        $this->assertNull($igvLine);

        // Revenue line should equal 450.00
        $revLine = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '70111'))->first();
        $this->assertNotNull($revLine);
        $this->assertEquals(450.00, (float) $revLine->credit);
    }

    /**
     * 3. Test Free-of-charge items (gratuita) have no impact on revenue.
     */
    public function test_free_of_charge_sale_items_have_no_revenue_impact(): void
    {
        $billing = $this->createBilling([
            'gravada'  => 200.00,
            'exonerada'=> 0.00,
            'inafecta' => 0.00,
            'gratuita' => 150.00, // Free sample product
            'igv'      => 36.00,
            'total'    => 236.00, // Total payable is only gravada + igv
        ]);

        $entry = $this->postingService->post($billing);

        $this->assertNotNull($entry);
        $this->assertEquals(236.00, (float) $entry->total_debit);
        $this->assertEquals(236.00, (float) $entry->total_credit);

        // Revenue is strictly 200.00 (gratuita 150.00 does NOT impact revenue)
        $revLine = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '70111'))->first();
        $this->assertEquals(200.00, (float) $revLine->credit);
    }

    /**
     * 4. Test Sale + Partial Credit Note.
     */
    public function test_sale_plus_partial_credit_note_creates_balanced_offsetting_entries(): void
    {
        // 1. Original sale: S/ 1000 + 180 IGV = S/ 1180
        $invoice = $this->createBilling([
            'gravada' => 1000.00,
            'igv'     => 180.00,
            'total'   => 1180.00,
        ]);
        $saleEntry = $this->postingService->post($invoice);
        $this->assertNotNull($saleEntry);

        // 2. Partial Credit note for S/ 200 + 36 IGV = S/ 236
        $creditNote = $this->createBilling([
            'idtipo_comprobante' => $this->typeCreditNote->id,
            'serie'              => 'FC01',
            'idfactura_anular'   => $invoice->id,
            'gravada'            => 200.00,
            'igv'                => 36.00,
            'total'              => 236.00,
        ]);

        $cnEntry = $this->postingService->post($creditNote);

        $this->assertNotNull($cnEntry);
        $this->assertEquals(VoucherType::ADJUSTMENT, $cnEntry->entry_type);
        $this->assertEquals(236.00, (float) $cnEntry->total_debit);
        $this->assertEquals(236.00, (float) $cnEntry->total_credit);

        // Lines: Debit 70111 for 200.00, Debit 40111 for 36.00, Credit 1212 for 236.00
        $revDebitLine = $cnEntry->lines()->whereHas('account', fn($q) => $q->where('code', '70111'))->first();
        $igvDebitLine = $cnEntry->lines()->whereHas('account', fn($q) => $q->where('code', '40111'))->first();
        $cxcCreditLine = $cnEntry->lines()->whereHas('account', fn($q) => $q->where('code', '1212'))->first();

        $this->assertEquals(200.00, (float) $revDebitLine->debit);
        $this->assertEquals(36.00, (float) $igvDebitLine->debit);
        $this->assertEquals(236.00, (float) $cxcCreditLine->credit);
    }

    /**
     * 5. Test Credit Purchase (Buy).
     */
    public function test_credit_purchase_creates_balanced_journal_entry(): void
    {
        $buy = $this->createBuy([
            'gravada'        => 3000.00,
            'exonerada'      => 0.00,
            'inafecta'       => 0.00,
            'igv'            => 540.00,
            'total'          => 3540.00,
        ]);

        $entry = $this->postingService->post($buy);

        $this->assertNotNull($entry);
        $this->assertEquals(3540.00, (float) $entry->total_debit);
        $this->assertEquals(3540.00, (float) $entry->total_credit);

        // Lines: Debit 6011 for 3000, Debit 40112 for 540, Credit 4212 for 3540
        $purchaseLine = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '6011'))->first();
        $payableLine  = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '4212'))->first();

        $this->assertEquals(3000.00, (float) $purchaseLine->debit);
        $this->assertEquals(3540.00, (float) $payableLine->credit);
    }

    /**
     * 6. Test Installment Collection (PaymentReceived).
     */
    public function test_installment_collection_creates_balanced_journal_entry(): void
    {
        $invoice = $this->createBilling([
            'gravada' => 1000.00,
            'igv'     => 180.00,
            'total'   => 1180.00,
        ]);
        $this->postingService->post($invoice);

        // Customer pays installment 1: S/ 590.00 in cash
        $paymentEvent = new PaymentReceived(
            document: $invoice,
            amount: 590.00,
            paymentMethodId: 1, // Cash
            installmentNumber: 1,
            userId: $this->user->id
        );

        $entry = $this->postingService->post($paymentEvent);

        $this->assertNotNull($entry);
        $this->assertEquals(590.00, (float) $entry->total_debit);
        $this->assertEquals(590.00, (float) $entry->total_credit);

        // Lines: Debit 1011 (Cash) for 590.00, Credit 1212 (CxC) for 590.00
        $cashLine = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '1011'))->first();
        $cxcLine  = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '1212'))->first();

        $this->assertEquals(590.00, (float) $cashLine->debit);
        $this->assertEquals(590.00, (float) $cxcLine->credit);
    }

    /**
     * 7. Test Voiding Billing creates Reversal Contra-Entry and never deletes.
     */
    public function test_voiding_billing_creates_reversal_contra_entry_and_marks_original_reversed(): void
    {
        $invoice = $this->createBilling([
            'gravada' => 800.00,
            'igv'     => 144.00,
            'total'   => 944.00,
        ]);

        $originalEntry = $this->postingService->post($invoice);
        $this->assertEquals(JournalStatus::POSTED, $originalEntry->status);

        // Void the billing via event
        $voidEvent = new BillingVoided(
            billing: $invoice,
            reason: 'Error en RUC de cliente'
        );

        $reversalEntry = $this->postingService->post($voidEvent);

        $this->assertNotNull($reversalEntry);
        $this->assertEquals(VoucherType::REVERSAL, $reversalEntry->entry_type);
        $this->assertEquals(944.00, (float) $reversalEntry->total_debit);
        $this->assertEquals(944.00, (float) $reversalEntry->total_credit);

        // Original entry must be marked REVERSED and NOT deleted
        $freshOriginal = $originalEntry->fresh();
        $this->assertEquals(JournalStatus::REVERSED, $freshOriginal->status);
        $this->assertEquals($reversalEntry->id, $freshOriginal->reversed_by_entry_id);
        $this->assertEquals($originalEntry->id, $reversalEntry->reverses_entry_id);

        $cxcReversalLine = $reversalEntry->lines()->whereHas('account', fn($q) => $q->where('code', '1212'))->first();
        $this->assertEquals(944.00, (float) $cxcReversalLine->credit);
    }

    /**
     * 8. Test Idempotency: Reprocessing does not create a duplicate entry.
     */
    public function test_idempotency_prevents_duplicate_entries_on_reprocessing(): void
    {
        $invoice = $this->createBilling([
            'gravada' => 500.00,
            'igv'     => 90.00,
            'total'   => 590.00,
        ]);

        $entry1 = $this->postingService->post($invoice);
        $entry2 = $this->postingService->post($invoice);

        $this->assertEquals($entry1->id, $entry2->id);

        $count = JournalEntry::where('source_type', get_class($invoice))
            ->where('source_id', $invoice->id)
            ->where('event_key', 'posted')
            ->count();

        $this->assertEquals(1, $count);
    }

    /**
     * 9. Test Single Accounting Origin: ActivityTransaction referencing core voucher does NOT create entry.
     */
    public function test_activity_transaction_referencing_core_voucher_does_not_generate_journal_entry(): void
    {
        // Transaction referencing billing_id
        $testBilling = $this->createBilling();
        $txWithBilling = $this->createActivityTransaction([
            'amount'     => 1500.00,
            'billing_id' => $testBilling->id, // references core voucher
        ]);

        $entry = $this->postingService->post($txWithBilling);
        $this->assertNull($entry);

        // Transaction referencing buy_id
        $testBuy = $this->createBuy();
        $txWithBuy = $this->createActivityTransaction([
            'transaction_type' => 'EXPENSE',
            'amount'           => 800.00,
            'buy_id'           => $testBuy->id, // references core voucher
        ]);

        $entryBuy = $this->postingService->post($txWithBuy);
        $this->assertNull($entryBuy);
    }

    /**
     * 10. Test Autonomous ActivityTransaction creates balanced entry.
     */
    public function test_autonomous_activity_transaction_creates_balanced_entry(): void
    {
        $autonomousTx = $this->createActivityTransaction([
            'transaction_type' => 'INCOME',
            'amount'           => 750.00,
            'billing_id'       => null,
            'buy_id'           => null,
            'sale_note_id'     => null,
            'concept'          => 'Venta directa en chacra con recibo interno',
        ]);

        $entry = $this->postingService->post($autonomousTx);

        $this->assertNotNull($entry);
        $this->assertEquals(750.00, (float) $entry->total_debit);
        $this->assertEquals(750.00, (float) $entry->total_credit);

        // Lines: Debit 1012 for 750.00, Credit 759 for 750.00
        $cashLine = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '1012'))->first();
        $revLine  = $entry->lines()->whereHas('account', fn($q) => $q->where('code', '759'))->first();

        $this->assertEquals(750.00, (float) $cashLine->debit);
        $this->assertEquals(750.00, (float) $revLine->credit);
    }

    /**
     * 11. Test Closed Period records in next open period with Regularization memo.
     */
    public function test_closed_period_records_in_next_open_period_with_regularization_memo(): void
    {
        // Create an explicitly locked period for 2025-01
        $lockedPeriod = AccountingPeriod::updateOrCreate(
            ['period_code' => '2025-01'],
            [
                'fiscal_year' => 2025,
                'month'       => 1,
                'start_date'  => '2025-01-01',
                'end_date'    => '2025-01-31',
                'status'      => 'LOCKED',
            ]
        );

        $invoiceFromLockedPeriod = $this->createBilling([
            'fecha_emision' => '2025-01-15',
            'gravada'       => 100.00,
            'igv'           => 18.00,
            'total'         => 118.00,
        ]);

        $entry = $this->postingService->post($invoiceFromLockedPeriod);

        $this->assertNotNull($entry);
        $this->assertNotEquals($lockedPeriod->id, $entry->accounting_period_id);
        $this->assertStringContainsString('[REGULARIZACIÓN PERÍODO CERRADO 2025-01]', $entry->concept);
        $this->assertEquals(118.00, (float) $entry->total_debit);
        $this->assertEquals(118.00, (float) $entry->total_credit);
    }

    /**
     * 12. Test Backfill command is repeatable without duplicate changes.
     */
    public function test_backfill_command_is_repeatable_without_changes(): void
    {
        $this->createBilling([
            'serie'       => 'FBK1',
            'correlativo' => '000001',
            'gravada'     => 500.00,
            'igv'         => 90.00,
            'total'       => 590.00,
        ]);

        $today = now()->toDateString();

        // 1st run
        $this->artisan("accounting:backfill --from={$today} --to={$today}")
            ->assertExitCode(0);

        $entriesCountAfterRun1 = JournalEntry::count();

        // 2nd run with exact same parameters
        $this->artisan("accounting:backfill --from={$today} --to={$today}")
            ->assertExitCode(0);

        $entriesCountAfterRun2 = JournalEntry::count();

        // No duplicate entries created
        $this->assertEquals($entriesCountAfterRun1, $entriesCountAfterRun2);
    }

    /**
     * 13. Test Posting Failures are logged and can be reprocessed.
     */
    public function test_posting_failures_are_logged_and_reprocessed(): void
    {
        $billing = $this->createBilling([
            'serie'       => 'F999',
            'correlativo' => '000099',
            'gravada'     => 100.00,
            'igv'         => 18.00,
            'total'       => 118.00,
        ]);

        // Manually record a failure
        $failure = $this->postingService->recordFailure(
            'App\Events\SaleCompleted',
            $billing,
            new \Exception('Simulated network timeout during posting')
        );

        $this->assertDatabaseHas('accounting_posting_failures', [
            'id'          => $failure->id,
            'source_type' => get_class($billing),
            'source_id'   => $billing->id,
            'status'      => 'FAILED',
        ]);

        // Reprocess failure via service
        $success = $this->postingService->reprocessFailure($failure->id);
        $this->assertTrue($success);

        $freshFailure = $failure->fresh();
        $this->assertEquals('REPROCESSED', $freshFailure->status);
        $this->assertNotNull($freshFailure->reprocessed_at);

        // A JournalEntry should now exist
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => get_class($billing),
            'source_id'   => $billing->id,
            'event_key'   => 'posted',
        ]);
    }
}
