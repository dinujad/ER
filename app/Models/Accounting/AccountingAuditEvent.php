<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;

class AccountingAuditEvent extends Model
{
    protected $table = 'accounting_audit_events';

    protected $fillable = [
        'business_id', 'user_id', 'action', 'subject_type', 'subject_id',
        'before_state', 'after_state', 'reason',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
    ];
}
