<?php

namespace App\Services;

use App\Models\SalesPersonExpense;
use App\Models\SalesRoute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SalesPersonExpenseReportService
{
    public function build(string $from, string $to, Request $request): array
    {
        $salesPersonId = $request->get('sales_person_id');
        $type = $request->get('type');
        $hasSlip = $request->get('has_slip');
        $routeId = $request->get('route_id');

        $query = SalesPersonExpense::query()
            ->with([
                'user:id,name,phone',
                'routeRun.salesRoute',
            ])
            ->whereBetween('expense_date', [$from, $to]);

        if ($salesPersonId) {
            $query->where('user_id', $salesPersonId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($hasSlip === 'yes') {
            $query->whereNotNull('slip_path');
        } elseif ($hasSlip === 'no') {
            $query->whereNull('slip_path');
        }

        if ($routeId) {
            $query->whereHas('routeRun', fn ($q) => $q->where('sales_route_id', $routeId));
        }

        $expenses = $query
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $byPerson = $expenses
            ->groupBy('user_id')
            ->map(function (Collection $group, $userId) {
                $first = $group->first();

                return (object) [
                    'user_id' => $userId,
                    'name' => $first->user?->name ?? 'Unknown',
                    'count' => $group->count(),
                    'total' => round((float) $group->sum('amount'), 2),
                    'with_slip' => $group->whereNotNull('slip_path')->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $byType = collect(SalesPersonExpense::typeOptions())
            ->map(function (string $label, string $key) use ($expenses) {
                $group = $expenses->where('type', $key);

                return (object) [
                    'type' => $key,
                    'label' => $label,
                    'count' => $group->count(),
                    'total' => round((float) $group->sum('amount'), 2),
                ];
            })
            ->filter(fn ($row) => $row->count > 0)
            ->sortByDesc('total')
            ->values();

        return [
            'expenses' => $expenses,
            'byPerson' => $byPerson,
            'byType' => $byType,
            'total' => round((float) $expenses->sum('amount'), 2),
            'count' => $expenses->count(),
            'withSlipCount' => $expenses->whereNotNull('slip_path')->count(),
            'personCount' => $byPerson->count(),
            'salesPersons' => User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']),
            'routes' => SalesRoute::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'sales_person_id' => $salesPersonId,
                'type' => $type,
                'has_slip' => $hasSlip,
                'route_id' => $routeId,
            ],
        ];
    }
}
