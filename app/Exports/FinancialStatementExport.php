<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FinancialStatementExport implements FromView, ShouldAutoSize
{
    public function __construct(
        private readonly array $reportData,
        private readonly string $title = 'Estado Financiero'
    ) {}

    public function view(): View
    {
        return view('admin.accounting.statements.statement_excel', [
            'title'          => $this->title,
            'template'       => $this->reportData['template'] ?? [],
            'period'         => $this->reportData['period'] ?? [],
            'filters'        => $this->reportData['filters'] ?? [],
            'has_comparison' => $this->reportData['has_comparison'] ?? false,
            'comparison'     => $this->reportData['comparison'] ?? null,
            'lines'          => $this->reportData['lines'] ?? [],
            'totals'         => $this->reportData['totals'] ?? [],
            'net_result'     => $this->reportData['net_result'] ?? 0.00,
            'business'       => Business::query()->find(1),
            'generatedAt'    => now(),
        ]);
    }
}
