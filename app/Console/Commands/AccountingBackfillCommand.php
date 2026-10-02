<?php

namespace App\Console\Commands;

use App\Models\ActivityTransaction;
use App\Models\ArchingCash;
use App\Models\Billing;
use App\Models\Buy;
use App\Models\JournalEntry;
use App\Models\RdrCutTransfer;
use App\Models\RdrInternalLoan;
use App\Models\SaleNote;
use App\Services\Accounting\AccountingEventMapper;
use App\Services\Accounting\JournalPostingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class AccountingBackfillCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:backfill
                            {--from= : Start date filter (YYYY-MM-DD)}
                            {--to= : End date filter (YYYY-MM-DD)}
                            {--dry-run : Simulate accounting mapping without persisting to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill historical operational transactions into double-entry journal entries idempotently';

    public function __construct(
        protected JournalPostingService $postingService,
        protected AccountingEventMapper $mapper
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fromDate = $this->option('from');
        $toDate = $this->option('to');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("=== ERP-FVC: Automated Accounting Backfill ===");
        if ($dryRun) {
            $this->warn("MODE: DRY RUN (no entries will be written to database)");
        }
        if ($fromDate || $toDate) {
            $this->line("Date filter: " . ($fromDate ?: 'beginning') . " to " . ($toDate ?: 'now'));
        }

        $stats = [
            'billings'          => ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => 0],
            'sale_notes'        => ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => 0],
            'buys'              => ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => 0],
            'cash_sessions'     => ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => 0],
            'cut_transfers'     => ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => 0],
            'internal_loans'    => ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => 0],
            'activity_tx'       => ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => 0],
        ];

        // 1. Billings (Facturas, Boletas, Notas de Crédito, Notas de Débito)
        $billingsQuery = Billing::query()->with(['customer', 'typeDocument']);
        if ($fromDate) {
            $billingsQuery->whereDate('fecha_emision', '>=', $fromDate);
        }
        if ($toDate) {
            $billingsQuery->whereDate('fecha_emision', '<=', $toDate);
        }

        $billings = $billingsQuery->orderBy('id')->get();
        foreach ($billings as $billing) {
            $this->processRecord($billing, 'billings', $dryRun, $stats);
        }

        // 2. Sale Notes
        $saleNotesQuery = SaleNote::query()->with(['cliente']);
        if ($fromDate) {
            $saleNotesQuery->whereDate('fecha_emision', '>=', $fromDate);
        }
        if ($toDate) {
            $saleNotesQuery->whereDate('fecha_emision', '<=', $toDate);
        }

        $saleNotes = $saleNotesQuery->orderBy('id')->get();
        foreach ($saleNotes as $saleNote) {
            $this->processRecord($saleNote, 'sale_notes', $dryRun, $stats);
        }

        // 3. Buys (Compras)
        $buysQuery = Buy::query()->with(['provider']);
        if ($fromDate) {
            $buysQuery->whereDate('fecha_emision', '>=', $fromDate);
        }
        if ($toDate) {
            $buysQuery->whereDate('fecha_emision', '<=', $toDate);
        }

        $buys = $buysQuery->orderBy('id')->get();
        foreach ($buys as $buy) {
            $this->processRecord($buy, 'buys', $dryRun, $stats);
        }

        // 4. ArchingCash (Cajas cerradas)
        $cashQuery = ArchingCash::query()->where('estado', 'CERRADO');
        if ($fromDate) {
            $cashQuery->whereDate('fecha_fin', '>=', $fromDate);
        }
        if ($toDate) {
            $cashQuery->whereDate('fecha_fin', '<=', $toDate);
        }

        $cashes = $cashQuery->orderBy('id')->get();
        foreach ($cashes as $cash) {
            $this->processRecord($cash, 'cash_sessions', $dryRun, $stats);
        }

        // 5. CUT Transfers
        $cutQuery = RdrCutTransfer::query();
        if ($fromDate) {
            $cutQuery->whereDate('transfer_date', '>=', $fromDate);
        }
        if ($toDate) {
            $cutQuery->whereDate('transfer_date', '<=', $toDate);
        }

        $cutTransfers = $cutQuery->orderBy('id')->get();
        foreach ($cutTransfers as $cut) {
            $this->processRecord($cut, 'cut_transfers', $dryRun, $stats);
        }

        // 6. Internal Loans
        $loanQuery = RdrInternalLoan::query();
        if ($fromDate) {
            $loanQuery->whereDate('issue_date', '>=', $fromDate);
        }
        if ($toDate) {
            $loanQuery->whereDate('issue_date', '<=', $toDate);
        }

        $loans = $loanQuery->orderBy('id')->get();
        foreach ($loans as $loan) {
            $this->processRecord($loan, 'internal_loans', $dryRun, $stats);
        }

        // 7. Activity Transactions (Only autonomous ones without billing_id, buy_id, or sale_note_id)
        $txQuery = ActivityTransaction::query()
            ->whereNull('billing_id')
            ->whereNull('buy_id')
            ->whereNull('sale_note_id');
        if ($fromDate) {
            $txQuery->whereDate('transaction_date', '>=', $fromDate);
        }
        if ($toDate) {
            $txQuery->whereDate('transaction_date', '<=', $toDate);
        }

        $transactions = $txQuery->orderBy('id')->get();
        foreach ($transactions as $tx) {
            $this->processRecord($tx, 'activity_tx', $dryRun, $stats);
        }

        // Display results
        $tableData = [];
        $totalCreated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($stats as $entity => $data) {
            $tableData[] = [
                ucwords(str_replace('_', ' ', $entity)),
                $data['processed'],
                $data['created'],
                $data['skipped'],
                $data['errors'],
            ];
            $totalCreated += $data['created'];
            $totalSkipped += $data['skipped'];
            $totalErrors  += $data['errors'];
        }

        $this->table(
            ['Entity', 'Total Records', 'Entries Created', 'Skipped (Existing)', 'Errors'],
            $tableData
        );

        $this->info("Summary: {$totalCreated} entries created, {$totalSkipped} skipped, {$totalErrors} errors.");
        return $totalErrors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process an individual record.
     */
    protected function processRecord(mixed $record, string $category, bool $dryRun, array &$stats): void
    {
        $stats[$category]['processed']++;

        try {
            if ($dryRun) {
                // Check if already posted
                $sourceType = get_class($record);
                $sourceId = $record->getKey();

                $alreadyPosted = JournalEntry::where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->exists();

                if ($alreadyPosted) {
                    $stats[$category]['skipped']++;
                } else {
                    $mapped = $this->mapper->map($record);
                    if ($mapped) {
                        $stats[$category]['created']++;
                    } else {
                        $stats[$category]['skipped']++;
                    }
                }
            } else {
                $entry = $this->postingService->post($record);
                if (!$entry) {
                    $stats[$category]['skipped']++;
                } elseif ($entry->wasRecentlyCreated) {
                    $stats[$category]['created']++;
                } else {
                    $stats[$category]['skipped']++;
                }
            }
        } catch (Throwable $e) {
            $stats[$category]['errors']++;
            $this->error("Error processing {$category} #{$record->getKey()}: " . $e->getMessage());
        }
    }
}
