<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\AccountingAuditEvent;
use Illuminate\Routing\Controller;

class AccountingAuditController extends Controller
{
    public function index()
    {
        $events = AccountingAuditEvent::query()->orderByDesc('id')->paginate(50);

        return view('accounting::phase2.audit.index', compact('events'));
    }
}
