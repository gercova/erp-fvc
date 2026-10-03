<?php

namespace App\Services\Agreements;

use App\Models\Agreement;
use App\Models\AgreementAddendum;
use App\Models\ServiceEngagement;
use App\Models\TechnologicalService;
use Illuminate\Support\Facades\DB;

class AgreementCodeService
{
    /**
     * Generate sequential code for an Agreement using lockForUpdate.
     * Format: CONV-YYYY-0001
     */
    public function generateAgreementCode(?int $year = null): string {
        $year = $year ?? (int) date('Y');
        $prefix = "CONV-{$year}-";

        return DB::transaction(function () use ($year, $prefix) {
            $lastCode = Agreement::withTrashed()
                ->where('code', 'LIKE', "{$prefix}%")
                ->whereRaw("code REGEXP '^{$prefix}[0-9]+$'")
                ->lockForUpdate()
                ->orderByRaw('LENGTH(code) DESC, code DESC')
                ->value('code');

            $nextNumber = 1;
            if ($lastCode && preg_match('/^CONV-\d{4}-(\d+)$/', $lastCode, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate sequential code for a TechnologicalService using lockForUpdate.
     * Format: SERV-YYYY-0001
     */
    public function generateServiceCode(?int $year = null): string {
        $year = $year ?? (int) date('Y');
        $prefix = "SERV-{$year}-";

        return DB::transaction(function () use ($year, $prefix) {
            $lastCode = TechnologicalService::withTrashed()
                ->where('code', 'LIKE', "{$prefix}%")
                ->whereRaw("code REGEXP '^{$prefix}[0-9]+$'")
                ->lockForUpdate()
                ->orderByRaw('LENGTH(code) DESC, code DESC')
                ->value('code');

            $nextNumber = 1;
            if ($lastCode && preg_match('/^SERV-\d{4}-(\d+)$/', $lastCode, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate sequential code for a ServiceEngagement using lockForUpdate.
     * Format: ORD-YYYY-0001
     */
    public function generateEngagementCode(?int $year = null): string {
        $year   = $year ?? (int) date('Y');
        $prefix = "ORD-{$year}-";

        return DB::transaction(function () use ($year, $prefix) {
            $lastCode = ServiceEngagement::withTrashed()
                ->where('code', 'LIKE', "{$prefix}%")
                ->whereRaw("code REGEXP '^{$prefix}[0-9]+$'")
                ->lockForUpdate()
                ->orderByRaw('LENGTH(code) DESC, code DESC')
                ->value('code');

            $nextNumber = 1;
            if ($lastCode && preg_match('/^ORD-\d{4}-(\d+)$/', $lastCode, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Generate sequential code for an AgreementAddendum using lockForUpdate.
     * Format: ADD-{AGREEMENT_CODE}-01
     */
    public function generateAddendumCode(Agreement $agreement): string {
        $prefix = "ADD-{$agreement->code}-";

        return DB::transaction(function () use ($agreement, $prefix) {
            $lastNumber = AgreementAddendum::withTrashed()
                ->where('agreement_id', $agreement->id)
                ->lockForUpdate()
                ->max('addendum_number');

            $nextNumber = ((int) $lastNumber) + 1;

            return $prefix . str_pad((string) $nextNumber, 2, '0', STR_PAD_LEFT);
        });
    }
}
