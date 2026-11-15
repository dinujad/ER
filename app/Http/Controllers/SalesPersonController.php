<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SalesRoute;
use App\Services\SalesPersonAssignmentService;
use App\Services\SalesPersonCredentialsNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SalesPersonController extends Controller
{
    public function __construct(
        protected SalesPersonCredentialsNotifier $credentialsNotifier,
        protected SalesPersonAssignmentService $assignments
    ) {}

    public function index()
    {
        $salesPersons = User::where('role', 'sales_person')
            ->with('salesRoute')
            ->withCount(['customers', 'salesOrders as orders_count'])
            ->orderBy('name')
            ->get();
        $salesRoutes = SalesRoute::active()->orderBy('name')->get(['id', 'name', 'code']);

        return view('erp.sales_persons.index', compact('salesPersons', 'salesRoutes'));
    }

    public function create()
    {
        $salesRoutes = SalesRoute::active()->orderBy('name')->get();

        return view('erp.sales_persons.create', compact('salesRoutes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'bank_account_number' => 'nullable|string|max:50',
            'sales_route_id' => 'nullable|exists:sales_routes,id',
        ]);

        $password = Str::random(10);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $password,
            'role' => 'sales_person',
            'bank_account_number' => $request->bank_account_number,
            'sales_route_id' => $request->sales_route_id ?: null,
        ]);

        $smsStatus = $this->credentialsNotifier->sendViaSms($user, $password);

        if ($smsStatus['ok']) {
            return redirect()->route('sales-persons.index')
                ->with('success', "Sales person added. Login credentials sent via SMS to {$user->phone}.");
        }

        return redirect()->route('sales-persons.index')
            ->with('warning', "Sales person added but SMS failed: {$smsStatus['error']} Temporary password: {$password}");
    }

    public function edit($id)
    {
        $salesPerson = User::where('role', 'sales_person')->with('salesRoute')->findOrFail($id);
        $counts = $this->assignments->countsFor($salesPerson);
        $orphanOrderCount = $this->assignments->orphanOrderCount($salesPerson);
        $assignedCustomers = $this->assignments->assignedCustomers($salesPerson);
        $otherSalesPersons = $this->assignments->otherSalesPersons($salesPerson->id);
        $salesRoutes = SalesRoute::active()->orderBy('name')->get();

        return view('erp.sales_persons.edit', compact('salesPerson', 'counts', 'orphanOrderCount', 'assignedCustomers', 'otherSalesPersons', 'salesRoutes'));
    }

    public function update(Request $request, $id)
    {
        $salesPerson = User::where('role', 'sales_person')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone' => 'required|string|max:20',
            'bank_account_number' => 'nullable|string|max:50',
            'sales_route_id' => 'nullable|exists:sales_routes,id',
            'password' => ['nullable', 'confirmed', Password::min(6)],
            'send_password_sms' => 'nullable|boolean',
        ]);

        $salesPerson->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'bank_account_number' => $request->bank_account_number,
            'sales_route_id' => $request->sales_route_id ?: null,
        ]);

        $message = 'Sales person updated successfully.';

        if ($request->filled('password')) {
            $plainPassword = $request->password;
            $salesPerson->update(['password' => $plainPassword]);

            if ($request->boolean('send_password_sms')) {
                $smsStatus = $this->credentialsNotifier->sendViaSms($salesPerson->fresh(), $plainPassword);
                if ($smsStatus['ok']) {
                    $message .= ' New password saved and sent via SMS.';
                } else {
                    return redirect()->route('sales-persons.edit', $salesPerson->id)
                        ->with('warning', "Password updated but SMS failed: {$smsStatus['error']}");
                }
            } else {
                $message .= ' Password updated (not sent via SMS).';
            }
        }

        return redirect()->route('sales-persons.index')->with('success', $message);
    }

    public function assignRoute(Request $request, $id)
    {
        $salesPerson = User::where('role', 'sales_person')->findOrFail($id);
        $request->validate([
            'sales_route_id' => 'nullable|exists:sales_routes,id',
        ]);

        $salesPerson->update([
            'sales_route_id' => $request->sales_route_id ?: null,
        ]);

        $label = $salesPerson->fresh()->salesRoute?->name ?? 'None';

        return redirect()->route('sales-persons.index')
            ->with('success', "{$salesPerson->name} assigned to route: {$label}.");
    }

    public function reassign(Request $request, $id)
    {
        $salesPerson = User::where('role', 'sales_person')->findOrFail($id);

        $request->validate([
            'reassign_to' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'sales_person')),
                Rule::notIn([$salesPerson->id]),
            ],
            'move_all' => 'nullable|boolean',
            'move_orphan_orders' => 'nullable|boolean',
            'customer_ids' => 'required_unless:move_all,1|array|min:1',
            'customer_ids.*' => [
                'integer',
                Rule::exists('customers', 'id')->where(fn ($q) => $q->where('added_by', $salesPerson->id)),
            ],
        ]);

        $target = User::where('role', 'sales_person')->findOrFail($request->reassign_to);

        if ($request->boolean('move_all')) {
            $moved = $this->assignments->transferAll($salesPerson, $target);
            $message = "Moved {$moved['customers']} customer(s) and {$moved['orders']} order(s) to {$target->name}.";
        } else {
            $moved = $this->assignments->transferCustomers(
                $salesPerson,
                $target,
                $request->input('customer_ids', []),
                $request->boolean('move_orphan_orders')
            );
            $message = "Moved {$moved['customers']} customer(s) and {$moved['orders']} order(s) to {$target->name}.";
            if ($moved['orphan_orders'] > 0) {
                $message .= " Also moved {$moved['orphan_orders']} unlinked order(s).";
            }
        }

        if ($moved['customers'] === 0 && $moved['orders'] === 0 && ($moved['orphan_orders'] ?? 0) === 0) {
            return redirect()->route('sales-persons.edit', $salesPerson->id)
                ->with('warning', 'No customers or orders were moved. Check your selection and try again.');
        }

        return redirect()->route('sales-persons.edit', $salesPerson->id)
            ->with('success', $message);
    }

    public function destroy(Request $request, $id)
    {
        $salesPerson = User::where('role', 'sales_person')->findOrFail($id);
        $counts = $this->assignments->countsFor($salesPerson);
        $hasAssignments = $counts['customers'] > 0 || $counts['orders'] > 0;

        if ($hasAssignments) {
            $others = $this->assignments->otherSalesPersons($salesPerson->id);
            if ($others->isEmpty()) {
                return redirect()->route('sales-persons.index')
                    ->with('warning', 'Cannot delete: this sales person has customers or orders but no other sales person exists to reassign them to. Add another sales person first.');
            }

            $request->validate([
                'reassign_to' => [
                    'required',
                    Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', 'sales_person')),
                    Rule::notIn([$salesPerson->id]),
                ],
            ]);

            $target = User::where('role', 'sales_person')->findOrFail($request->reassign_to);
            $moved = $this->assignments->transferAll($salesPerson, $target);

            $salesPerson->delete();

            return redirect()->route('sales-persons.index')
                ->with('success', "Sales person deleted. {$moved['customers']} customer(s) and {$moved['orders']} order(s) moved to {$target->name}.");
        }

        $salesPerson->delete();

        return redirect()->route('sales-persons.index')
            ->with('success', 'Sales person deleted.');
    }

    public function resendCredentials($id)
    {
        $salesPerson = User::where('role', 'sales_person')->findOrFail($id);
        $newPassword = Str::random(10);

        $salesPerson->update([
            'password' => $newPassword,
        ]);

        $smsStatus = $this->credentialsNotifier->sendViaSms($salesPerson->fresh(), $newPassword);

        if ($smsStatus['ok']) {
            return back()->with('success', "New password sent via SMS to {$salesPerson->phone}.");
        }

        return back()->with('warning', "Password reset but SMS failed: {$smsStatus['error']} New password: {$newPassword}");
    }
}
