<?php

namespace App\Http\Controllers;

use App\Models\SalesRoute;
use App\Services\RouteCashSettlementService;
use App\Services\SalesRouteRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SalesPersonRouteController extends Controller
{
    public function __construct(protected SalesRouteRunService $runs) {}

    public function show()
    {
        $user = auth()->user();
        $route = $user->salesRoute;
        $activeRun = $this->runs->activeRunFor($user);
        $recentRuns = $this->runs->recentFor($user);
        $routeCash = $activeRun ? app(RouteCashSettlementService::class)->forRun($activeRun) : null;

        if (! $this->runs->isReady()) {
            session()->now('error', 'Route trips are not set up yet. Ask admin to run database migrations.');
        }

        return view('sales_person.routes.show', compact('route', 'activeRun', 'recentRuns', 'routeCash'));
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'driver_name' => 'required|string|max:255',
            'assistants' => 'nullable|string|max:2000',
        ]);

        try {
            $this->runs->start(auth()->user(), [
                'driver_name' => $data['driver_name'],
                'assistants' => $data['assistants'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('sales-person.route.show')
            ->with('success', 'Route started. Start time recorded. You can add expenses along the way.');
    }

    public function complete(): RedirectResponse
    {
        try {
            $run = $this->runs->complete(auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('sales-person.route.show')
            ->with('success', 'Route completed. Duration: ' . $run->durationLabel() . '.');
    }
}
