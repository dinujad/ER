<?php

namespace App\Services\Accounting;

use App\Models\Accounting\AccountingAuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AccountingAuditService
{
    public static function record(
        string $action,
        mixed $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?string $reason = null,
        int $businessId = 0,
        ?int $userId = null
    ): ?AccountingAuditEvent {
        if (! Schema::hasTable('accounting_audit_events')) {
            return null;
        }

        $subjectType = null;
        $subjectId = null;
        if ($subject instanceof Model) {
            $subjectType = $subject->getMorphClass();
            $subjectId = $subject->getKey();
        } elseif (is_object($subject) && isset($subject->id)) {
            $subjectId = (int) $subject->id;
            $subjectType = $subject::class;
        }

        return AccountingAuditEvent::create([
            'business_id' => $businessId,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before_state' => $before,
            'after_state' => $after,
            'reason' => $reason,
        ]);
    }
}
