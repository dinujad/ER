<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAuditEvent;
use Illuminate\Database\Eloquent\Model;

class AccountingAuditService
{
    public static function record(
        string $action,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        int $businessId = 0,
        ?int $userId = null
    ): AccountingAuditEvent {
        return AccountingAuditEvent::create([
            'business_id' => $businessId,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'before_state' => $before,
            'after_state' => $after,
            'reason' => $reason,
        ]);
    }
}
