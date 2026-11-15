<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\SaleReturnClaimService;
use App\Services\WholesaleCustomerProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'retail');

        $query = Customer::with('addedBy')->latest();

        if ($tab === 'wholesale') {
            $query->wholesale();
        } else {
            $query->retail();
        }

        if ($request->filled('search')) {
            $needle = '%' . trim((string) $request->search) . '%';
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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $addedBy = $request->input('added_by', $request->input('sales_person_id'));
        if ($addedBy === 'system') {
            $query->whereNull('added_by');
        } elseif (filled($addedBy)) {
            $query->where('added_by', $addedBy);
        }

        $customers = $query->withCount('orders')->withReturnCreditTotals()->paginate(25)->withQueryString();

        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']);

        return view('erp.customers.index', compact('customers', 'tab', 'salesPersons'));
    }

    public function create(Request $request)
    {
        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']);
        $defaultType = $request->get('type', 'wholesale');

        return view('erp.customers.create', compact('salesPersons', 'defaultType'));
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
            'customer_type' => 'required|in:wholesale,retail',
            'sales_person_id' => 'nullable|exists:users,id',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        $brDocumentPath = null;
        if ($request->hasFile('br_document')) {
            $brDocumentPath = $request->file('br_document')->store('customers/br-documents', 'public');
        }

        $addedBy = null;
        if ($request->filled('sales_person_id')) {
            $sp = User::where('role', 'sales_person')->find($request->sales_person_id);
            $addedBy = $sp?->id;
        }

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'business_name' => $request->business_name,
            'business_address' => $request->business_address,
            'br_number' => $request->br_number,
            'tin' => $request->tin,
            'br_document_path' => $brDocumentPath,
            'credit_limit' => $request->credit_limit ?? 0,
            'added_by' => $addedBy,
            'customer_type' => $request->customer_type,
        ]);

        $tab = $customer->customer_type === 'wholesale' ? 'wholesale' : 'retail';

        return redirect()
            ->route('erp.customers.index', ['tab' => $tab])
            ->with('success', 'Customer "' . $customer->display_name . '" added successfully. You can create an order now.');
    }

    public function storePreviousReturn(Request $request, Customer $customer, SaleReturnClaimService $claims)
    {
        if ($customer->customer_type !== 'wholesale') {
            return back()->with('error', 'Previous return credit is only for wholesale businesses.');
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
        if ($customer->customer_type !== 'wholesale') {
            return redirect()
                ->route('erp.customers.index', ['tab' => 'retail'])
                ->with('error', 'Retail customers do not have a wholesale profile.');
        }

        $customer->load('addedBy');
        $profile = $profileService->build($customer, $request);

        return view('erp.customers.show', array_merge(compact('customer'), $profile));
    }

    public function edit(Customer $customer)
    {
        $salesPersons = User::where('role', 'sales_person')->orderBy('name')->get(['id', 'name']);
        $defaultType = $customer->customer_type ?: 'wholesale';

        return view('erp.customers.create', compact('customer', 'salesPersons', 'defaultType'));
    }

    public function update(Request $request, Customer $customer)
    {
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
            'customer_type' => 'required|in:wholesale,retail',
            'sales_person_id' => 'nullable|exists:users,id',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        if ($request->hasFile('br_document')) {
            $data['br_document_path'] = $request->file('br_document')->store('customers/br-documents', 'public');
        }
        unset($data['br_document']);

        $addedBy = null;
        if ($request->filled('sales_person_id')) {
            $sp = User::where('role', 'sales_person')->find($request->sales_person_id);
            $addedBy = $sp?->id;
        }
        $data['added_by'] = $addedBy;
        unset($data['sales_person_id']);

        $customer->update($data);

        if ($customer->customer_type === 'wholesale') {
            return redirect()
                ->route('erp.customers.show', $customer)
                ->with('success', 'Customer updated.');
        }

        return redirect()
            ->route('erp.customers.index', ['tab' => 'retail'])
            ->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $tab = $customer->customer_type === 'wholesale' ? 'wholesale' : 'retail';

        if ($customer->orders()->exists() || $customer->saleReturns()->exists()) {
            return redirect()
                ->route('erp.customers.index', ['tab' => $tab])
                ->with('error', 'Cannot delete this customer — they have existing orders or returns.');
        }

        if ($customer->br_document_path) {
            Storage::disk('public')->delete($customer->br_document_path);
        }

        $name = $customer->display_name;
        $customer->delete();

        return redirect()
            ->route('erp.customers.index', ['tab' => $tab])
            ->with('success', "Customer \"{$name}\" deleted successfully.");
    }
}
