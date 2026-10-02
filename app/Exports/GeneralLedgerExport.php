<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GeneralLedgerExport implements FromView, ShouldAutoSize
{
    public function __construct(
        private readonly array $reportData,
        private readonly string $title = 'Libro Mayor - Formato 6.1'
    ) {
    }

    public function view(): View
    {
        return view('admin.accounting.reports.ledger_excel', [
            'title'       => $this->title,
            'accounts'    => $this->reportData['accounts'] ?? [],
            'summary'     => $this->reportData['summary'] ?? [],
            'filters'     => $this->reportData['filters'] ?? [],
            'business'    => Business::query()->find(1),
            'generatedAt' => now(),
        ]);
    }
}
