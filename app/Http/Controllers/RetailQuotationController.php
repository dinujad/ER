<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;

class RetailQuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->retailQuotations()
            ->with(['customer', 'items.product', 'items.variation', 'salesPerson'])
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $needle = trim((string) $request->q);
            $query->where(function ($q) use ($needle) {
                $q->where('order_number', 'like', '%' . $needle . '%')
                    ->orWhere('invoice_number', 'like', '%' . $needle . '%');
            });
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        $totalQuoted = (float) (clone $query)->sum('total_amount');
        $quotations = $query->paginate(25)->withQueryString();

        $customers = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")
            ->get(['id', 'name', 'business_name']);

        return view('erp.retail-quotations.index', compact(
            'quotations',
            'customers',
            'totalQuoted'
        ));
    }

    public function show(Order $order)
    {
        $this->assertRetailQuotation($order);

        $order->load(['customer', 'items.product', 'items.variation', 'salesPerson', 'store']);

        return view('erp.retail-quotations.show', compact('order'));
    }

    public function openInPos(Order $order)
    {
        $this->assertRetailQuotation($order);

        $load = app(PosSaleController::class)->loadSale($order);
        $data = $load->getData(true);
        if (empty($data['success'])) {
            return back()->with('error', $data['message'] ?? 'Could not open this quotation in POS.');
        }

        return redirect()
            ->route('pos.index')
            ->with('success', 'Quotation ' . $order->order_number . ' loaded in POS. Complete the sale when ready.');
    }

    protected function assertRetailQuotation(Order $order): void
    {
        if (!$order->isRetailQuotation()) {
            abort(404);
        }
    }
}
