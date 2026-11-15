<?php

namespace App\Http\Controllers;

use App\Models\SalesRoutePettyCash;
use App\Models\SalesRouteRun;
use App\Models\User;
use App\Services\RouteCashSettlementService;
use App\Services\SalesRouteRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class AdminSalesRouteRunController extends Controller
{
    public function __construct(protected SalesRouteRunService $runs) {}

    public function live()
    {
        if (! $this->runs->isReady()) {
            session()->now('error', 'Route trips are not set up yet. Run database migrations.');

            return view('erp.sales_routes.live', ['runs' => collect(), 'settlements' => collect()]);
        }

        $runs = SalesRouteRun::query()
            ->active()
            ->with(['salesPerson', 'salesRoute'])
            ->withSum('expenses as expenses_total', 'amount')
            ->withCount('expenses')
            ->orderByDesc('started_at')
            ->get();

        $settlements = app(RouteCashSettlementService::class)->forRuns($runs);

        return view('erp.sales_routes.live', compact('runs', 'settlements'));
    }

    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $salesPersonId = $request->get('sales_person_id');
        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']);

        if (! $this->runs->isReady()) {
            session()->now('error', 'Route trips are not set up yet. Run database migrations.');
            $runs = new LengthAwarePaginator([], 0, 30, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('erp.sales_routes.runs', [
                'runs' => $runs,
                'salesPersons' => $salesPersons,
                'status' => $status,
                'salesPersonId' => $salesPersonId,
                'settlements' => collect(),
            ]);
        }

        $query = SalesRouteRun::query()
            ->with(['salesPerson', 'salesRoute'])
            ->withSum('expenses as expenses_total', 'amount')
            ->withCount('expenses')
            ->orderByDesc('started_at');

        if ($status === SalesRouteRun::STATUS_ACTIVE) {
            $query->active();
        } elseif ($status === SalesRouteRun::STATUS_COMPLETED) {
            $query->completed();
        }

        if ($salesPersonId) {
            $query->where('user_id', $salesPersonId);
        }

        $runs = $query->paginate(30)->withQueryString();
        $settlements = app(RouteCashSettlementService::class)->forRuns($runs->getCollection());

        return view('erp.sales_routes.runs', compact('runs', 'salesPersons', 'status', 'salesPersonId', 'settlements'));
    }

    public function show(SalesRouteRun $run)
    {
        $run->load(['salesPerson', 'salesRoute', 'expenses']);
        $settlement = app(RouteCashSettlementService::class)->forRun($run);

        return view('erp.sales_routes.run-show', compact('run', 'settlement'));
    }

    public function storePettyCash(Request $request, SalesRouteRun $run): RedirectResponse
    {
        if (!Schema::hasTable('sales_route_petty_cash')) {
            return back()->with('error', 'Petty cash is not set up yet. Run database migrations.');
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255',
        ]);

        SalesRoutePettyCash::create([
            'sales_route_run_id' => $run->id,
            'amount' => round((float) $data['amount'], 2),
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Petty cash Rs '.number_format((float) $data['amount'], 2).' added for '.($run->salesPerson?->name ?? 'this route').'.');
    }
}
