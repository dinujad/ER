<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingPeriod;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class AccountingPeriodService
{
    /**
     * Ensure the given posting date falls in an open period (or no periods defined yet).
     * When periods exist for the business, closed periods block posting.
     */
    public static function assertOpenForDate(string $date, int $businessId = 0): void
    {
        if (! Schema::hasTable('accounting_periods')) {
            return;
        }

        $period = AccountingPeriod::query()
            ->where('business_id', $businessId)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->first();

        if ($period && $period->isClosed()) {
            throw new RuntimeException(
                "Accounting period '{$period->name}' is closed. Posting on {$date} is not allowed."
            );
        }
    }

    public static function close(AccountingPeriod $period, ?int $userId = null): AccountingPeriod
    {
        $period->status = 'closed';
        $period->closed_at = now();
        $period->closed_by = $userId;
        $period->save();

        AccountingAuditService::record(
            'period.closed',
            $period,
            null,
            $period->toArray(),
            'Period closed',
            (int) $period->business_id,
            $userId
        );

        return $period;
    }

    public static function reopen(AccountingPeriod $period, ?int $userId = null, ?string $reason = null): AccountingPeriod
    {
        $before = $period->toArray();
        $period->status = 'open';
        $period->closed_at = null;
        $period->closed_by = null;
        $period->save();

        AccountingAuditService::record(
            'period.reopened',
            $period,
            $before,
            $period->toArray(),
            $reason ?? 'Period reopened',
            (int) $period->business_id,
            $userId
        );

        return $period;
    }
}
