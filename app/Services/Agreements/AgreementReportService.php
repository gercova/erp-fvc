<?php

namespace App\Services\Agreements;

use App\Enums\InstallmentStatus;
use App\Enums\ObligationStatus;
use App\Models\Agreement;
use App\Models\AgreementInstallment;
use App\Models\AgreementObligation;
use App\Models\Business;
use App\Models\JournalEntryLine;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgreementReportService
{
    /**
     * Compile comprehensive Scheduled vs. Invoiced vs. Collected revenue report.
     * Acceptance Criteria: Agreement revenue matches across the report, vouchers, and the General Ledger.
     */
    public function getRevenueReport(array $filters = []): array
    {
        $query = Agreement::with([
            'client.tipoDocumento',
            'productiveActivity',
            'installments.billing',
            'installments.saleNote',
        ])
        ->when(!empty($filters['agreement_id']), fn($q) => $q->where('id', $filters['agreement_id']))
        ->when(!empty($filters['client_id']), fn($q) => $q->where('client_id', $filters['client_id']))
        ->when(!empty($filters['productive_activity_id']), fn($q) => $q->where('productive_activity_id', $filters['productive_activity_id']))
        ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']));

        $agreements = $query->get();

        $rows = [];
        $grandTotalScheduled = 0.00;
        $grandTotalInvoiced  = 0.00;
        $grandTotalCollected = 0.00;
        $grandTotalPending   = 0.00;
        $allBillingIds       = [];
        $allSaleNoteIds      = [];

        foreach ($agreements as $agreement) {
            $installments = $agreement->installments;

            if (!empty($filters['year'])) {
                $installments = $installments->filter(function ($inst) use ($filters) {
                    return $inst->due_date && Carbon::parse($inst->due_date)->year == $filters['year'];
                });
            }

            if (!empty($filters['month'])) {
                $installments = $installments->filter(function ($inst) use ($filters) {
                    return $inst->due_date && Carbon::parse($inst->due_date)->month == $filters['month'];
                });
            }

            $scheduled = (float) $installments->sum('amount');

            $invoiced = (float) $installments->filter(function ($inst) {
                return $inst->isInvoiced() || $inst->isCollected();
            })->sum('amount');

            $collected = (float) $installments->filter(function ($inst) {
                return $inst->isCollected();
            })->sum('amount');

            $pending = max(0.00, $scheduled - $collected);

            $grandTotalScheduled += $scheduled;
            $grandTotalInvoiced  += $invoiced;
            $grandTotalCollected += $collected;
            $grandTotalPending   += $pending;

            $linkedBillingIds = $installments->pluck('billing_id')->filter()->toArray();
            $linkedSaleNoteIds = $installments->pluck('sale_note_id')->filter()->toArray();

            $allBillingIds = array_merge($allBillingIds, $linkedBillingIds);
            $allSaleNoteIds = array_merge($allSaleNoteIds, $linkedSaleNoteIds);

            $rows[] = [
                'agreement_id'       => $agreement->id,
                'agreement_code'     => $agreement->code,
                'agreement_title'    => $agreement->title,
                'counterparty'       => $agreement->client?->nombres ?? 'No asignado',
                'counterparty_doc'   => $agreement->client?->nro_documento ?? '-',
                'productive_activity'=> $agreement->productiveActivity?->name ?? 'Sin centro de costos',
                'currency'           => $agreement->currency ?? 'PEN',
                'total_agreement'    => (float) $agreement->total_amount,
                'scheduled'          => $scheduled,
                'invoiced'           => $invoiced,
                'collected'          => $collected,
                'pending'            => $pending,
                'installments_count' => $installments->count(),
                'compliance_pct'     => $agreement->compliancePercentage(),
                'installments'       => $installments,
            ];
        }

        // Cross-check with General Ledger journal lines for Track B vouchers
        $glCreditsTotal = 0.00;
        if (!empty($allBillingIds) || !empty($allSaleNoteIds)) {
            $glCreditsTotal = (float) JournalEntryLine::whereHas('journalEntry', function ($jq) use ($allBillingIds, $allSaleNoteIds) {
                $jq->where(function ($sub) use ($allBillingIds, $allSaleNoteIds) {
                    if (!empty($allBillingIds)) {
                        $sub->orWhere(fn($b) => $b->where('source_type', 'BILLING')->whereIn('source_id', $allBillingIds));
                    }
                    if (!empty($allSaleNoteIds)) {
                        $sub->orWhere(fn($s) => $s->where('source_type', 'SALE_NOTE')->whereIn('source_id', $allSaleNoteIds));
                    }
                });
            })
            ->whereHas('account', fn($aq) => $aq->where('code', 'like', '70%'))
            ->sum('credit');
        }

        return [
            'rows'                   => $rows,
            'grand_total_scheduled'  => round($grandTotalScheduled, 2),
            'grand_total_invoiced'   => round($grandTotalInvoiced, 2),
            'grand_total_collected'  => round($grandTotalCollected, 2),
            'grand_total_pending'    => round($grandTotalPending, 2),
            'gl_revenue_credits'     => round($glCreditsTotal, 2),
            'filters'                => $filters,
        ];
    }

    /**
     * Compile overdue obligations compliance report.
     */
    public function getOverdueObligationsReport(array $filters = []): array
    {
        $today = now()->toDateString();

        $query = AgreementObligation::with(['agreement.client', 'responsibleUser', 'verifier'])
            ->where(function ($q) use ($today) {
                $q->where('status', ObligationStatus::OVERDUE->value)
                  ->orWhere(function ($sub) use ($today) {
                      $sub->whereIn('status', [ObligationStatus::PENDING->value, ObligationStatus::IN_PROGRESS->value])
                          ->where('due_date', '<', $today);
                  });
            })
            ->when(!empty($filters['agreement_id']), fn($q) => $q->where('agreement_id', $filters['agreement_id']))
            ->when(!empty($filters['responsible_party']), fn($q) => $q->where('responsible_party', $filters['responsible_party']))
            ->orderBy('due_date');

        $obligations = $query->get()->map(function ($obl) {
            $dueDate = Carbon::parse($obl->due_date);
            $obl->days_overdue = max(0, (int) now()->startOfDay()->diffInDays($dueDate->startOfDay()));
            return $obl;
        });

        $institutionCount  = $obligations->where('responsible_party', 'OUR_INSTITUTION')->count();
        $counterpartyCount = $obligations->where('responsible_party', 'COUNTERPARTY')->count();
        $jointCount        = $obligations->where('responsible_party', 'MUTUAL')->count();

        return [
            'obligations'        => $obligations,
            'total_overdue'      => $obligations->count(),
            'institution_count'  => $institutionCount,
            'counterparty_count' => $counterpartyCount,
            'joint_count'        => $jointCount,
        ];
    }

    /**
     * Render Revenue Report PDF via DomPDF.
     */
    public function generateRevenuePdf(array $filters = [])
    {
        $reportData = $this->getRevenueReport($filters);
        $business   = Business::first();

        $data = [
            'report'   => $reportData,
            'business' => $business,
            'filters'  => $filters,
            'generated_at' => now(),
        ];

        return Pdf::loadView('admin.agreements.reports.revenue_pdf', $data)
            ->setPaper('a4', 'landscape');
    }

    /**
     * Render Overdue Obligations PDF via DomPDF.
     */
    public function generateOverdueObligationsPdf(array $filters = [])
    {
        $reportData = $this->getOverdueObligationsReport($filters);
        $business   = Business::first();

        $data = [
            'report'   => $reportData,
            'business' => $business,
            'filters'  => $filters,
            'generated_at' => now(),
        ];

        return Pdf::loadView('admin.agreements.reports.overdue_obligations_pdf', $data)
            ->setPaper('a4', 'portrait');
    }
}
