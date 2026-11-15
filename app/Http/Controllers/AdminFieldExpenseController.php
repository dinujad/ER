<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\SalesPersonExpense;
use App\Models\User;
use App\Models\VehicleDailyRunItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminFieldExpenseController extends Controller
{
    public function salesPerson(Request $request)
    {
        [$month, $start, $end] = $this->resolveMonthRange($request);

        $query = SalesPersonExpense::query()
            ->with('user')
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()]);

        if ($request->filled('sales_person_id')) {
            $query->where('user_id', $request->sales_person_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $this->applySlipFilter($query, $request, 'slip_path');

        $totalAmount = (float) (clone $query)->sum('amount');
        $withSlipCount = (clone $query)->whereNotNull('slip_path')->count();

        $expenses = (clone $query)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']);
        $todayCount = SalesPersonExpense::query()->whereDate('created_at', now()->toDateString())->count();

        return view('erp.field-expenses.sales-person', compact(
            'expenses',
            'salesPersons',
            'month',
            'start',
            'totalAmount',
            'withSlipCount',
            'todayCount'
        ) + ['monthOptions' => $this->monthOptions()]);
    }

    public function destroyToday()
    {
        $deleted = 0;
        SalesPersonExpense::query()
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$deleted) {
                foreach ($rows as $expense) {
                    $this->deleteExpense($expense);
                    $deleted++;
                }
            });

        return back()->with(
            'success',
            $deleted > 0
                ? 'Deleted '.$deleted.' sales person expense'.($deleted === 1 ? '' : 's').' added today.'
                : 'No sales person expenses were added today.'
        );
    }

    public function destroy(SalesPersonExpense $expense)
    {
        $this->deleteExpense($expense);

        return back()->with('success', 'Expense deleted.');
    }

    protected function deleteExpense(SalesPersonExpense $expense): void
    {
        if ($expense->slip_path) {
            Storage::disk('public')->delete($expense->slip_path);
        }
        $expense->delete();
    }

    public function drivers(Request $request)
    {
        [$month, $start, $end] = $this->resolveMonthRange($request);

        $query = VehicleDailyRunItem::query()
            ->select('vehicle_daily_run_items.*')
            ->join('vehicle_daily_runs', 'vehicle_daily_runs.id', '=', 'vehicle_daily_run_items.vehicle_daily_run_id')
            ->with(['run.driver', 'run.vehicle'])
            ->whereBetween('vehicle_daily_runs.run_date', [$start->toDateString(), $end->toDateString()]);

        if ($request->filled('driver_id')) {
            $query->where('vehicle_daily_runs.driver_id', $request->driver_id);
        }

        if ($request->filled('type')) {
            $query->where('vehicle_daily_run_items.type', $request->type);
        }

        if ($request->has_slip === 'yes') {
            $query->whereNotNull('vehicle_daily_run_items.slip_path');
        } elseif ($request->has_slip === 'no') {
            $query->whereNull('vehicle_daily_run_items.slip_path');
        }

        $totalAmount = (float) (clone $query)->sum('vehicle_daily_run_items.cost');
        $withSlipCount = (clone $query)->whereNotNull('vehicle_daily_run_items.slip_path')->count();

        $items = (clone $query)
            ->orderByDesc('vehicle_daily_runs.run_date')
            ->orderByDesc('vehicle_daily_run_items.id')
            ->paginate(25)
            ->withQueryString();

        $drivers = Driver::orderBy('name')->get(['id', 'name']);

        return view('erp.field-expenses.drivers', compact(
            'items',
            'drivers',
            'month',
            'start',
            'totalAmount',
            'withSlipCount'
        ) + ['monthOptions' => $this->monthOptions()]);
    }

    /** @return array{0: string, 1: Carbon, 2: Carbon} */
    protected function resolveMonthRange(Request $request): array
    {
        $month = $request->input('month', now()->format('Y-m'));
        try {
            $start = Carbon::parse($month . '-01')->startOfMonth();
        } catch (\Throwable) {
            $month = now()->format('Y-m');
            $start = now()->startOfMonth();
        }

        return [$month, $start, $start->copy()->endOfMonth()];
    }

    protected function applySlipFilter($query, Request $request, string $column): void
    {
        if ($request->has_slip === 'yes') {
            $query->whereNotNull($column);
        } elseif ($request->has_slip === 'no') {
            $query->whereNull($column);
        }
    }

    protected function monthOptions(): \Illuminate\Support\Collection
    {
        return collect(range(0, 11))->map(function ($i) {
            $d = now()->subMonths($i);

            return ['value' => $d->format('Y-m'), 'label' => $d->format('F Y')];
        });
    }
}
