<?php

namespace App\Http\Controllers;

use App\Models\ManufacturingJob;
use App\Services\CostAnalystHubService;
use Illuminate\Http\Request;

class CostAnalystController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()?->can_access('manufacturing.costs')) {
                abort(403, 'Cost Analyst is available to administrators only.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $data = app(CostAnalystHubService::class)->dashboard($from, $to, $request);

        return view('erp.cost-analyst.index', [
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'defaultMargin' => config('cost_analyst.default_margin_percent', 35),
        ]);
    }

    public function show(Request $request, ManufacturingJob $job)
    {
        $job->load(['product.latestRecipe', 'packingBatches']);
        $data = app(CostAnalystHubService::class)->jobDetail($job, $request);

        return view('erp.cost-analyst.show', [
            'job' => $job,
            'data' => $data,
            'defaultMargin' => config('cost_analyst.default_margin_percent', 35),
        ]);
    }
}
