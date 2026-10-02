<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TrialBalanceExport implements FromView, ShouldAutoSize
{
    public function __construct(
        private readonly array $reportData,
        private readonly string $title = 'Balance de Comprobación'
    ) {
    }

    public function view(): View
    {
        return view('admin.accounting.reports.trial_balance_excel', [
            'title'        => $this->title,
            'accounts'     => $this->reportData['accounts'] ?? [],
            'totals'       => $this->reportData['totals'] ?? [],
            'verification' => $this->reportData['verification'] ?? [],
            'filters'      => $this->reportData['filters'] ?? [],
            'business'     => Business::query()->find(1),
            'generatedAt'  => now(),
        ]);
    }
}
