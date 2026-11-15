<?php

namespace Modules\Accounting\Http\Controllers;

use App\Models\ManufacturingJob;
use App\Services\Accounting\JobCostAccountingService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Accounting\Http\Controllers\Concerns\RequiresAccountingPermission;

class JobCostController extends Controller
{
    use RequiresAccountingPermission;

    public function index(Request $request)
    {
        $jobs = new LengthAwarePaginator([], 0, 25);
        $summaries = [];

        if (Schema::hasTable('manufacturing_jobs')) {
            $q = ManufacturingJob::query()->orderByDesc('id');
            if ($search = $request->get('q')) {
                $q->where('job_number', 'like', "%{$search}%");
            }
            $jobs = $q->paginate(25)->withQueryString();
            foreach ($jobs as $job) {
                try {
                    $summaries[$job->id] = JobCostAccountingService::summarizeJob($job);
                } catch (\Throwable) {
                    $summaries[$job->id] = [
                        'total_cost' => 0,
                        'ledger_debits' => JobCostAccountingService::ledgerDebitsForJob((int) $job->id),
                    ];
                }
            }
        }

        return view('accounting::phase3.job_costs.index', compact('jobs', 'summaries'));
    }

    public function show($id)
    {
        $job = ManufacturingJob::findOrFail($id);
        $summary = JobCostAccountingService::summarizeJob($job);
        $ledgerLines = JobCostAccountingService::ledgerLinesForJob((int) $job->id);

        return view('accounting::phase3.job_costs.show', compact('job', 'summary', 'ledgerLines'));
    }
}