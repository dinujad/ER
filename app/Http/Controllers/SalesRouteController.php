<?php

namespace App\Http\Controllers;

use App\Models\SalesRoute;
use App\Models\User;
use Illuminate\Http\Request;

class SalesRouteController extends Controller
{
    public function index()
    {
        $routes = SalesRoute::withCount([
            'salesPersons',
        ])->orderBy('name')->paginate(25);

        return view('erp.sales_routes.index', compact('routes'));
    }

    public function create()
    {
        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name', 'phone', 'sales_route_id']);

        return view('erp.sales_routes.create', compact('salesPersons'));
    }

    public function store(Request $request)
    {
        $this->normalizeRouteInput($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:sales_routes,code',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'sales_person_ids' => 'nullable|array',
            'sales_person_ids.*' => 'integer|exists:users,id',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['from_location'] = null;
        $data['to_location'] = null;

        $route = SalesRoute::create(collect($data)->except('sales_person_ids')->all());
        $this->syncSalesPersons($route, $request->input('sales_person_ids', []));

        return redirect()->route('sales-routes.index')->with('success', 'Route created.');
    }

    public function edit(SalesRoute $salesRoute)
    {
        $salesRoute->load(['salesPersons' => fn ($q) => $q->orderBy('name')]);
        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name', 'phone', 'sales_route_id']);

        return view('erp.sales_routes.edit', compact('salesRoute', 'salesPersons'));
    }

    public function update(Request $request, SalesRoute $salesRoute)
    {
        $this->normalizeRouteInput($request);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:sales_routes,code,' . $salesRoute->id,
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
            'sales_person_ids' => 'nullable|array',
            'sales_person_ids.*' => 'integer|exists:users,id',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['from_location'] = null;
        $data['to_location'] = null;

        $salesRoute->update(collect($data)->except('sales_person_ids')->all());
        $this->syncSalesPersons($salesRoute, $request->input('sales_person_ids', []));

        return redirect()->route('sales-routes.index')->with('success', 'Route updated.');
    }

    public function destroy(SalesRoute $salesRoute)
    {
        if ($salesRoute->salesPersons()->exists()) {
            return back()->with('error', 'Cannot delete route while sales persons are assigned. Reassign them first.');
        }

        if ($salesRoute->runs()->exists()) {
            return back()->with('error', 'Cannot delete route while trip history exists. Keep it inactive instead.');
        }

        $salesRoute->delete();

        return redirect()->route('sales-routes.index')->with('success', 'Route deleted.');
    }

    protected function normalizeRouteInput(Request $request): void
    {
        $code = trim((string) $request->input('code', ''));
        $request->merge([
            'code' => $code === '' ? null : $code,
        ]);
    }

    /**
     * @param  array<int, int|string>  $salesPersonIds
     */
    protected function syncSalesPersons(SalesRoute $route, array $salesPersonIds): void
    {
        $ids = User::query()
            ->where('role', 'sales_person')
            ->whereIn('id', $salesPersonIds)
            ->pluck('id')
            ->all();

        User::query()
            ->where('role', 'sales_person')
            ->where('sales_route_id', $route->id)
            ->whereNotIn('id', $ids)
            ->update(['sales_route_id' => null]);

        if ($ids !== []) {
            User::query()
                ->where('role', 'sales_person')
                ->whereIn('id', $ids)
                ->update(['sales_route_id' => $route->id]);
        }
    }
}
