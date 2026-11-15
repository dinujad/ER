<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\SaleReturnClaimService;
use App\Services\WholesaleCustomerProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesPersonCustomerController extends Controller
{
    public function index(Request $request)
    {
        $ownerIds = auth()->user()->visibleCustomerOwnerIds();
        $query = Customer::query()
            ->with('addedBy:id,name')
            ->whereIn('added_by', $ownerIds)
            ->latest();

        if ($request->filled('q')) {
            $needle = '%' . trim((string) $request->q) . '%';
            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('address', 'like', $needle)
                    ->orWhere('business_name', 'like', $needle)
                    ->orWhere('business_address', 'like', $needle)
                    ->orWhere('br_number', 'like', $needle)
                    ->orWhere('tin', 'like', $needle);
            });
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . trim((string) $request->phone) . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . trim((string) $request->email) . '%');
        }

        if ($request->filled('business_name')) {
            $query->where('business_name', 'like', '%' . trim((string) $request->business_name) . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $customers = $query->withReturnCreditTotals()->paginate(20)->withQueryString();

        return view('sales_person.customers.index', compact('customers'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $needle = '%' . $q . '%';
        $ownerIds = auth()->user()->visibleCustomerOwnerIds();

        $customers = Customer::query()
            ->whereIn('added_by', $ownerIds)
            ->where(function ($builder) use ($needle) {
                $builder->where('name', 'like', $needle)
                    ->orWhere('phone', 'like', $needle)
                    ->orWhere('email', 'like', $needle)
                    ->orWhere('business_name', 'like', $needle)
                    ->orWhere('address', 'like', $needle);
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'phone', 'email', 'business_name', 'address', 'added_by']);

        $credits = app(SaleReturnClaimService::class)->breakdownForCustomers($customers->pluck('id')->all());

        return response()->json($customers->map(function (Customer $c) use ($credits) {
            $credit = $credits[(int) $c->id] ?? ['previous' => 0, 'system' => 0, 'full' => 0];

            return [
                'id' => $c->id,
                'name' => $c->name,
                'display_name' => $c->display_name,
                'phone' => $c->phone ?? '',
                'email' => $c->email ?? '',
                'business_name' => $c->business_name ?? '',
                'claim_previous' => $credit['previous'],
                'claim_system' => $credit['system'],
                'claim_full' => $credit['full'],
            ];
        })->values());
    }

    public function create()
    {
        return view('sales_person.customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:customers',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'business_name' => 'required|string|max:255',
            'business_address' => 'nullable|string|max:1000',
            'br_number' => 'nullable|string|max:100',
            'tin' => ['nullable', 'regex:/^\d{9}$/'],
            'br_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $brDocumentPath = null;
        if ($request->hasFile('br_document')) {
            $brDocumentPath = $request->file('br_document')->store('customers/br-documents', 'public');
        }

        Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'business_name' => $request->business_name,
            'business_address' => $request->business_address,
            'br_number' => $request->br_number,
            'tin' => $request->tin,
            'br_document_path' => $brDocumentPath,
            'added_by' => auth()->id(),
            'customer_type' => 'wholesale',
        ]);

        return redirect()->route('sales-person.customers.index')->with('success', 'Customer added successfully.');
    }

    public function storePreviousReturn(Request $request, Customer $customer, SaleReturnClaimService $claims)
    {
        $ownerIds = auth()->user()->visibleCustomerOwnerIds();
        if (!in_array((int) $customer->added_by, $ownerIds, true)) {
            abort(403, 'You can only add previous returns for businesses on your route.');
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:500',
        ]);

        $credit = $claims->addPreviousCredit($customer, (float) $data['amount'], $data['note'] ?? null, auth()->id());

        return back()->with('success', 'Previous return added. Rs '.number_format((float) $credit->amount, 2).' can be claimed for '.$customer->display_name.'.');
    }

    public function show(Request $request, Customer $customer, WholesaleCustomerProfileService $profileService)
    {
        $ownerIds = auth()->user()->visibleCustomerOwnerIds();
        if ($customer->customer_type !== 'wholesale' || !in_array((int) $customer->added_by, $ownerIds, true)) {
            abort(403, 'You can only view wholesale customers on your sales route.');
        }

        $customer->load('addedBy');
        $profile = $profileService->build($customer, $request);

        return view('sales_person.customers.show', array_merge(compact('customer'), $profile));
    }

    public function edit(Customer $customer)
    {
        $this->authorizeCustomer($customer);

        return view('sales_person.customers.create', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'business_name' => 'required|string|max:255',
            'business_address' => 'nullable|string|max:1000',
            'br_number' => 'nullable|string|max:100',
            'tin' => ['nullable', 'regex:/^\d{9}$/'],
            'br_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('br_document')) {
            $data['br_document_path'] = $request->file('br_document')->store('customers/br-documents', 'public');
        }
        unset($data['br_document']);

        $customer->update($data);

        return redirect()
            ->route('sales-person.customers.show', $customer)
            ->with('success', 'Customer updated.');
    }

    protected function authorizeCustomer(Customer $customer): void
    {
        $ownerIds = auth()->user()->visibleCustomerOwnerIds();
        if ($customer->customer_type !== 'wholesale' || !in_array((int) $customer->added_by, $ownerIds, true)) {
            abort(403, 'You can only edit wholesale customers on your sales route.');
        }
    }
}
