<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BudgetExecutionExport implements FromView, ShouldAutoSize
{
    protected array $executionData;
    protected ?array $matrixData;
    protected string $title;

    public function __construct(array $executionData, ?array $matrixData = null, string $title = 'Ejecución Presupuestal')
    {
        $this->executionData = $executionData;
        $this->matrixData = $matrixData;
        $this->title = $title;
    }

    public function view(): View
    {
        return view('admin.accounting.budget.budget_excel', [
            'title'         => $this->title,
            'budget'        => $this->executionData,
            'matrix'        => $this->matrixData,
            'business'      => Business::query()->find(1),
            'generatedAt'   => now(),
        ]);
    }
}
