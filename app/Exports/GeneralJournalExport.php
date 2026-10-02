<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GeneralJournalExport implements FromView, ShouldAutoSize
{
    public function __construct(
        private readonly array $reportData,
        private readonly string $title = 'Libro Diario - Formato 5.1'
    ) {
    }

    public function view(): View
    {
        return view('admin.accounting.reports.journal_excel', [
            'title'       => $this->title,
            'entries'     => $this->reportData['entries'] ?? [],
            'grandDebit'  => $this->reportData['grand_total_debit'] ?? 0.00,
            'grandCredit' => $this->reportData['grand_total_credit'] ?? 0.00,
            'isBalanced'  => $this->reportData['is_balanced'] ?? true,
            'filters'     => $this->reportData['filters'] ?? [],
            'business'    => Business::query()->find(1),
            'generatedAt' => now(),
        ]);
    }
}
