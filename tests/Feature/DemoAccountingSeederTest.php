<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\InstallmentStatus;
use App\Enums\ServiceSessionStatus;
use App\Models\AccountingPeriod;
use App\Models\Agreement;
use App\Models\AgreementInstallment;
use App\Models\ArchingCash;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Billing;
use App\Models\Buy;
use App\Models\JournalEntry;
use App\Models\RdrBankReconciliation;
use App\Models\ServiceAttendee;
use App\Models\ServiceEngagement;
use App\Models\ServiceSession;
use Database\Seeders\DemoAccountingSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoAccountingSeederTest extends TestCase
{
    /**
     * Test execution of DemoAccountingSeeder and verification of all required demo entities.
     */
    public function test_demo_accounting_seeder_populates_all_expected_records(): void
    {
        // 1. Run Seeder
        $exitCode = Artisan::call('db:seed', ['--class' => 'DemoAccountingSeeder']);
        $this->assertSame(0, $exitCode, 'DemoAccountingSeeder should execute cleanly with return code 0');

        // 2. Fiscal Period: 1 period spanning 3 months
        $period = AccountingPeriod::where('period_code', '2026-Q4')->first();
        $this->assertNotNull($period, 'Period 2026-Q4 must exist');
        $this->assertSame(2026, $period->fiscal_year);
        $this->assertSame('2026-10-01', $period->start_date->toDateString());
        $this->assertSame('2026-12-31', $period->end_date->toDateString());
        $this->assertSame('OPEN', $period->status);

        // 3. Sales: 40 vouchers (25 invoices + 15 receipts; taxable, exempt, non-taxable)
        $invoices = Billing::where('serie', 'F001')->where('idtipo_comprobante', 1)->get();
        $receipts = Billing::where('serie', 'B001')->where('idtipo_comprobante', 2)->get();
        $this->assertCount(25, $invoices, 'Must have exactly 25 invoices');
        $this->assertCount(15, $receipts, 'Must have exactly 15 receipts');
        $this->assertSame(40, $invoices->count() + $receipts->count(), 'Total sales vouchers must be exactly 40');

        $taxableSales = Billing::whereIn('idtipo_comprobante', [1, 2])
            ->whereBetween('fecha_emision', ['2026-10-01', '2026-12-31'])
            ->where('gravada', '>', 0)
            ->count();
        $exemptSales = Billing::whereIn('idtipo_comprobante', [1, 2])
            ->whereBetween('fecha_emision', ['2026-10-01', '2026-12-31'])
            ->where('exonerada', '>', 0)
            ->count();
        $inafectaSales = Billing::whereIn('idtipo_comprobante', [1, 2])
            ->whereBetween('fecha_emision', ['2026-10-01', '2026-12-31'])
            ->where('inafecta', '>', 0)
            ->count();

        $this->assertGreaterThan(0, $taxableSales, 'Must have taxable sales');
        $this->assertGreaterThan(0, $exemptSales, 'Must have exempt sales');
        $this->assertGreaterThan(0, $inafectaSales, 'Must have non-taxable sales');

        // 4. Credit Notes: 5 vouchers (3 on invoices, 2 on receipts)
        $creditNotes = Billing::whereIn('serie', ['FC01', 'BC01'])->where('idtipo_comprobante', 3)->get();
        $this->assertCount(5, $creditNotes, 'Must have exactly 5 credit notes');
        $this->assertSame(3, $creditNotes->where('serie', 'FC01')->count());
        $this->assertSame(2, $creditNotes->where('serie', 'BC01')->count());

        // 5. Purchases: 10 vouchers (5 cash, 5 credit)
        $buys = Buy::where('serie', 'E001')->whereBetween('fecha_emision', ['2026-10-01', '2026-12-31'])->get();
        $this->assertCount(10, $buys, 'Must have exactly 10 purchases');
        $this->assertSame(5, $buys->where('modo_pago', 1)->count(), 'Must have 5 cash purchases');
        $this->assertSame(5, $buys->where('modo_pago', 2)->count(), 'Must have 5 credit purchases');

        // 6. Cash Register Closings: 2 sessions
        $closings = ArchingCash::whereIn('fecha_inicio', ['2026-10-15', '2026-11-15'])->get();
        $this->assertCount(2, $closings, 'Must have exactly 2 cash register closings');
        foreach ($closings as $closing) {
            $this->assertSame(0, (int) $closing->estado, 'Closing must be closed (estado = 0)');
            $this->assertNotNull($closing->monto_final, 'Closing must have monto_final');
            $this->assertEquals((float) $closing->monto_estimado, (float) $closing->monto_final, 'Estimated must match final amount');
        }

        // 7. Bank Statement: 1 statement with 30 transactions (25 reconcilable)
        $bankAccount = BankAccount::where('account_number', '00-011-DEMO2026')->first();
        $this->assertNotNull($bankAccount, 'Demo bank account must exist');

        $movements = BankMovement::where('bank_account_id', $bankAccount->id)->get();
        $this->assertCount(30, $movements, 'Bank statement must have exactly 30 transactions');

        $reconciled = $movements->where('reconciliation_status', 'MATCHED');
        $inTransit = $movements->where('reconciliation_status', 'PENDING');
        $this->assertCount(25, $reconciled, 'Exactly 25 transactions must be reconcilable/matched');
        $this->assertCount(5, $inTransit, 'Exactly 5 transactions must be in-transit/pending');

        $reconciliation = RdrBankReconciliation::where('bank_account_id', $bankAccount->id)->first();
        $this->assertNotNull($reconciliation, 'RdrBankReconciliation header must exist');
        $this->assertSame('RECONCILED', $reconciliation->status);

        // 8. Agreements: 2 with payment schedules
        $agreements = Agreement::whereIn('code', ['CONV-2026-DEMO1', 'CONV-2026-DEMO2'])->get();
        $this->assertCount(2, $agreements, 'Must have exactly 2 agreements');
        $this->assertTrue($agreements->every(fn($a) => $a->status === AgreementStatus::ACTIVE));

        $installments = AgreementInstallment::whereIn('agreement_id', $agreements->pluck('id'))->get();
        $this->assertCount(5, $installments, 'Agreements must have 5 installments in total (3 + 2)');
        $this->assertSame(1, $installments->where('status', InstallmentStatus::INVOICED)->count());
        $this->assertNotNull($installments->where('status', InstallmentStatus::INVOICED)->first()->billing_id);

        // 9. Training Session: 1 engagement, 1 session, 15 participants
        $engagement = ServiceEngagement::where('code', 'SERV-2026-DEMO1')->first();
        $this->assertNotNull($engagement, 'Service engagement must exist');

        $sessions = ServiceSession::where('service_engagement_id', $engagement->id)->get();
        $this->assertCount(1, $sessions, 'Must have exactly 1 training session');
        $this->assertSame(ServiceSessionStatus::CONDUCTED, $sessions->first()->status);

        $attendees = ServiceAttendee::where('service_session_id', $sessions->first()->id)->get();
        $this->assertCount(15, $attendees, 'Training session must have exactly 15 participants');
        $this->assertTrue($attendees->every(fn($att) => $att->attended === true));

        // 10. Double-entry balancing: All posted journal entries in the period have Debits = Credits
        $unbalanced = DB::table('journal_entries as je')
            ->join('journal_entry_lines as jel', 'je.id', '=', 'jel.journal_entry_id')
            ->where('je.accounting_period_id', $period->id)
            ->selectRaw('je.id, ROUND(ABS(SUM(jel.debit) - SUM(jel.credit)), 2) as diff')
            ->groupBy('je.id')
            ->havingRaw('diff > 0.001')
            ->get();
        $this->assertTrue($unbalanced->isEmpty(), 'All journal entries in period must be strictly balanced');
    }

    /**
     * Test idempotency: Running the seeder twice produces the exact same counts.
     */
    public function test_demo_accounting_seeder_is_deterministic_and_idempotent(): void
    {
        // First run
        Artisan::call('db:seed', ['--class' => 'DemoAccountingSeeder']);
        $firstBillingCount = Billing::whereIn('serie', ['F001', 'B001', 'FC01', 'BC01'])->count();
        $firstBuyCount = Buy::where('serie', 'E001')->count();
        $firstBankMovCount = BankMovement::whereHas('bankAccount', fn($q) => $q->where('account_number', '00-011-DEMO2026'))->count();

        // Second run
        $exitCode = Artisan::call('db:seed', ['--class' => 'DemoAccountingSeeder']);
        $this->assertSame(0, $exitCode);

        $secondBillingCount = Billing::whereIn('serie', ['F001', 'B001', 'FC01', 'BC01'])->count();
        $secondBuyCount = Buy::where('serie', 'E001')->count();
        $secondBankMovCount = BankMovement::whereHas('bankAccount', fn($q) => $q->where('account_number', '00-011-DEMO2026'))->count();

        $this->assertSame($firstBillingCount, $secondBillingCount, 'Billing counts must be identical across runs');
        $this->assertSame($firstBuyCount, $secondBuyCount, 'Buy counts must be identical across runs');
        $this->assertSame($firstBankMovCount, $secondBankMovCount, 'Bank movement counts must be identical across runs');
    }
}
