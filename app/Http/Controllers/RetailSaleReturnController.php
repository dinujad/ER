<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SaleReturn;
use App\Services\SaleReturnService;
use Illuminate\Http\Request;

class RetailSaleReturnController extends Controller
{
    public function __construct(
        protected SaleReturnService $returnService
    ) {}

    public function index(Request $request)
    {
        $query = SaleReturn::query()
            ->where('channel', SaleReturn::CHANNEL_RETAIL)
            ->with(['order', 'customer', 'creator', 'items', 'refunds'])
            ->orderByDesc('return_date')
            ->orderByDesc('id');

        if ($request->filled('return_no')) {
            $query->where('return_no', 'like', '%' . trim((string) $request->return_no) . '%');
        }
        if ($request->filled('order_no')) {
            $needle = trim((string) $request->order_no);
            $query->whereHas('order', function ($q) use ($needle) {
                $q->where('order_number', 'like', '%' . $needle . '%')
                    ->orWhere('invoice_number', 'like', '%' . $needle . '%');
            });
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('return_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('return_date', '<=', $request->to_date);
        }

        $totalReturned = (float) (clone $query)->sum('total');
        $totalRefunded = (float) Payment::query()
            ->where('entry_type', Payment::ENTRY_REFUND)
            ->whereIn('sale_return_id', (clone $query)->select('sale_returns.id'))
            ->sum('amount');
        $returns = $query->paginate(25)->withQueryString();

        $customers = Customer::orderByRaw("COALESCE(NULLIF(TRIM(business_name), ''), name)")
            ->get(['id', 'name', 'business_name']);

        return view('erp.retail-sale-returns.index', compact(
            'returns',
            'customers',
            'totalReturned',
            'totalRefunded'
        ));
    }

    public function create(Request $request)
    {
        $order = null;
        if ($request->filled('order_id')) {
            $order = Order::with(['items.product', 'items.variation', 'customer', 'salesPerson', 'payments', 'saleReturns'])
                ->find($request->order_id);
        } elseif ($request->filled('q')) {
            $q = trim((string) $request->q);
            $order = Order::with(['items.product', 'items.variation', 'customer', 'salesPerson', 'payments', 'saleReturns'])
                ->retailSales()
                ->where(function ($query) use ($q) {
                    $query->where('order_number', $q)
                        ->orWhere('invoice_number', $q)
                        ->orWhere('order_number', 'like', '%' . $q . '%')
                        ->orWhere('invoice_number', 'like', '%' . $q . '%');
                })
                ->orderByDesc('id')
                ->first();
        }

        $wrongChannel = false;
        if ($order && !$order->isRetailSale()) {
            $wrongChannel = true;
            $order = null;
        }

        $recentSales = Order::with('customer')
            ->retailSales()
            ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_PAID, Order::STATUS_CREDIT])
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('erp.retail-sale-returns.create', compact('order', 'recentSales', 'wrongChannel'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'return_date' => 'required|date',
            'reason' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'refund_amount' => 'nullable|numeric|min:0',
            'refund_method' => 'nullable|in:cash,card,bank_transfer,cheque',
            'refund_reference' => 'nullable|string|max:255',
            'refund_notes' => 'nullable|string|max:1000',
        ]);

        $order = Order::with(['items.product', 'items.variation', 'customer', 'salesPerson', 'payments', 'saleReturns'])
            ->findOrFail($request->order_id);

        if (!$order->isRetailSale()) {
            return back()->withInput()->with('error', 'This is not a retail / POS sale. Use Sale Returns for wholesale invoices.');
        }

        $lines = [];
        foreach ($request->items as $row) {
            $qty = (float) ($row['quantity'] ?? 0);
            if ($qty > 0) {
                $lines[] = [
                    'order_item_id' => (int) $row['order_item_id'],
                    'quantity' => $qty,
                ];
            }
        }

        try {
            $return = $this->returnService->createReturn(
                $order,
                $lines,
                $request->return_date,
                $request->reason,
                auth()->id(),
                [
                    'refund_amount' => (float) ($request->refund_amount ?? 0),
                    'refund_method' => $request->refund_method ?? 'cash',
                    'refund_reference' => $request->refund_reference,
                    'refund_notes' => $request->refund_notes,
                    'auto_refund' => true,
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Could not record retail sale return. ' . $e->getMessage());
        }

        $refunded = (float) $return->refunds()->sum('amount');
        $msg = 'Retail return ' . $return->return_no . ' recorded. Items restocked to retail.';
        if ($refunded > 0) {
            $msg .= ' Refund Rs ' . number_format($refunded, 2) . ' posted.';
        }

        if ($request->boolean('from_pos')) {
            return redirect()->route('pos.index')->with('success', $msg);
        }

        return redirect()->route('retail-sale-returns.show', $return)->with('success', $msg);
    }

    public function show(SaleReturn $saleReturn)
    {
        if ($saleReturn->channel !== SaleReturn::CHANNEL_RETAIL) {
            abort(404);
        }

        $saleReturn->load(['order.customer', 'customer', 'items.product', 'items.variation', 'creator', 'refunds']);

        return view('erp.retail-sale-returns.show', compact('saleReturn'));
    }
}
