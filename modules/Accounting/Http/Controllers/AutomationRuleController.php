<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\Ai\WorkflowAutomationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class AutomationRuleController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $rules = Schema::hasTable('acct_automation_rules')
            ? DB::table('acct_automation_rules')
                ->where('business_id', $this->sessionBusinessId())
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('accounting::phase5.automation.index', [
            'pageTitle' => 'Automation Rules',
            'pageSub' => 'Allowlisted triggers & actions',
            'rules' => $rules,
            'triggers' => WorkflowAutomationService::TRIGGERS,
            'actions' => WorkflowAutomationService::ACTIONS,
        ]);
    }

    public function store(Request $request, WorkflowAutomationService $service)
    {
        $this->requireManage();
        $request->validate([
            'name' => 'required|string|max:160',
            'trigger' => 'required|string',
            'action' => 'required|string',
        ]);
        $service->createRule($this->sessionBusinessId(), $request->only([
            'name', 'trigger', 'action', 'conditions', 'action_config', 'schedule_cron', 'requires_approval', 'notify_recipient', 'active',
        ]), Auth::id());

        return redirect('accounting/phase5/automation')->with('status', 'Rule created.');
    }

    public function history()
    {
        $rows = Schema::hasTable('acct_automation_executions')
            ? DB::table('acct_automation_executions')
                ->where('business_id', $this->sessionBusinessId())
                ->orderByDesc('id')
                ->limit(100)
                ->get()
            : collect();

        return view('accounting::phase5.automation.history', [
            'pageTitle' => 'Automation History',
            'pageSub' => 'Idempotent executions',
            'executions' => $rows,
        ]);
    }

    public function toggle(int $id, Request $request, WorkflowAutomationService $service)
    {
        $this->requireManage();
        $service->setActive($this->sessionBusinessId(), $id, (bool) $request->boolean('active'));

        return redirect('accounting/phase5/automation')->with('status', 'Rule updated.');
    }
}
