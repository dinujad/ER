<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\Accounting\CustomReportBuilderService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class ScheduledReportController extends Controller
{
    use RequiresAccountingPermission;

    public function index()
    {
        $schedules = Schema::hasTable('acct_report_schedules')
            ? DB::table('acct_report_schedules')->orderByDesc('id')->paginate(20)
            : collect();
        $executions = Schema::hasTable('acct_report_executions')
            ? DB::table('acct_report_executions')->orderByDesc('id')->limit(20)->get()
            : collect();

        return view('accounting::phase4.scheduled.index', [
            'schedules' => $schedules,
            'executions' => $executions,
            'types' => CustomReportBuilderService::ALLOWED_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $this->requireManage();
        $data = $request->validate([
            'report_type' => 'required|string|max:60',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'config' => 'nullable|array',
            'next_run_at' => 'required|date',
        ]);

        DB::table('acct_report_schedules')->insert([
            'business_id' => (int) (session('business.id') ?? 0),
            'report_type' => $data['report_type'],
            'frequency' => $data['frequency'],
            'config' => json_encode($data['config'] ?? ['from' => date('Y-01-01'), 'to' => date('Y-m-d'), 'format' => 'csv']),
            'recipients' => json_encode([]),
            'next_run_at' => $data['next_run_at'],
            'status' => 'active',
            'created_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Schedule created.');
    }
}