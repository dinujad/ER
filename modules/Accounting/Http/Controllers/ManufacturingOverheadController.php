<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\Accounting\MfgOverheadRule;
use App\Models\Accounting\MfgOverheadRun;
use App\Services\Accounting\ManufacturingOverheadAllocationService;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;
use Modules\Accounting\Services\FlashService;

class ManufacturingOverheadController extends Controller
{
    use RequiresAccountingPermission;

    public function rulesIndex()
    {
        $rules = Schema::hasTable('mfg_overhead_rules')
            ? MfgOverheadRule::query()->orderByDesc('id')->paginate(25)
            : collect();

        return view('accounting::phase3.manufacturing_overhead.rules_index', compact('rules'));
    }

    public function rulesCreate()
    {
        return view('accounting::phase3.manufacturing_overhead.rules_create', [
            'defaultWip' => AccountingService::accountId(AccountingService::GL_WORK_IN_PROGRESS),
            'defaultPool' => AccountingService::accountId(AccountingService::GL_GENERAL_EXPENSE),
        ]);
    }

    public function rulesStore(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'allocation_base' => 'required|in:labor_hours,machine_hours,production_qty,labor_cost',
            'rate' => 'required|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0',
            'overhead_pool_account_id' => 'required|integer',
            'wip_account_id' => 'nullable|integer',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            ManufacturingOverheadAllocationService::createRule($data, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return redirect('accounting/manufacturing-overhead/rules');
    }

    public function approveRule($id)
    {
        $this->requireManage();
        $rule = MfgOverheadRule::findOrFail($id);
        try {
            ManufacturingOverheadAllocationService::approveRule($rule, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/manufacturing-overhead/rules');
    }

    public function activateRule($id)
    {
        $this->requireManage();
        $rule = MfgOverheadRule::findOrFail($id);
        try {
            ManufacturingOverheadAllocationService::activateRule($rule, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/manufacturing-overhead/rules');
    }

    public function allocationForm()
    {
        $rules = Schema::hasTable('mfg_overhead_rules')
            ? MfgOverheadRule::query()->whereIn('status', ['approved', 'active'])->orderBy('name')->get()
            : collect();

        return view('accounting::phase3.manufacturing_overhead.allocation', compact('rules'));
    }

    public function preview(Request $request)
    {
        $this->requireManage();
        $request->validate([
            'rule_id' => 'required|integer',
            'period_from' => 'required|date',
            'period_to' => 'required|date',
        ]);
        $rule = MfgOverheadRule::findOrFail($request->rule_id);
        try {
            $result = ManufacturingOverheadAllocationService::preview($rule, $request->period_from, $request->period_to);
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBackWithInput();
        }

        return view('accounting::phase3.manufacturing_overhead.preview', [
            'run' => $result['run'],
            'lines' => $result['lines'],
        ]);
    }

    public function postRun($runId)
    {
        $this->requireManage();
        $run = MfgOverheadRun::with('lines')->findOrFail($runId);
        try {
            ManufacturingOverheadAllocationService::postRun($run, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/manufacturing-overhead/history');
    }

    public function history()
    {
        $runs = Schema::hasTable('mfg_overhead_runs')
            ? MfgOverheadRun::query()->with('rule')->orderByDesc('id')->paginate(25)
            : collect();

        return view('accounting::phase3.manufacturing_overhead.history', compact('runs'));
    }

    public function reverseRun($id)
    {
        $this->requireManage();
        $run = MfgOverheadRun::with('lines')->findOrFail($id);
        try {
            ManufacturingOverheadAllocationService::reverseRun($run, Auth::id());
            (new FlashService())->onSave();
        } catch (\Throwable $e) {
            return (new FlashService())->onException($e)->redirectBack();
        }

        return redirect('accounting/manufacturing-overhead/history');
    }
}