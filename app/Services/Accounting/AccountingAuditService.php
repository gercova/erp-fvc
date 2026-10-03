<?php

namespace App\Services\Accounting;

use App\Models\AccountingAuditLog;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AccountingAuditService
{
    /**
     * Log an accounting security or operational action.
     */
    public static function log(
        string $action,
        ?Model $auditable = null,
        ?User $user = null,
        ?string $description = null,
        array $metadata = []
    ): AccountingAuditLog {
        $currentUser    = $user ?? auth()->user();
        $ip             = request()->ip() ?? '127.0.0.1';
        $userAgent      = request()->userAgent() ?? 'CLI/System';

        $entryNumber    = null;
        $periodCode     = null;

        if ($auditable instanceof JournalEntry) {
            $entryNumber    = $auditable->entry_number;
            $periodCode     = $auditable->period?->period_code;
        } elseif ($auditable instanceof AccountingPeriod) {
            $periodCode = $auditable->period_code;
        }

        return AccountingAuditLog::create([
            'user_id'        => $currentUser?->id,
            'action'         => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id'   => $auditable?->getKey(),
            'entry_number'   => $entryNumber,
            'period_code'    => $periodCode,
            'ip_address'     => $ip,
            'user_agent'     => $userAgent,
            'description'    => $description,
            'metadata'       => $metadata,
            'created_at'     => now(),
        ]);
    }
}
