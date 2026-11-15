<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Services\PurchaseReturnService;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    public function __construct(
        protected PurchaseReturnService $returnService
    ) {}

    public function index(Request $request)
    {
        $query = PurchaseReturn::query()
            ->with(['purchase', 'supplier', 'creator'])
            ->orderByDesc('return_date')
            ->orderByDesc('id');

        if ($request->filled('return_no')) {
            $query->where('return_no', 'like', '%' . trim((string) $request->return_no) . '%');
        }
        if ($request->filled('purchase_no')) {
            $query->whereHas('purchase', fn ($q) => $q->where('purchase_no', 'like', '%' . trim((string) $request->purchase_no) . '%'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('return_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('return_date', '<=', $request->to_date);
        }

        $totalReturned = (float) (clone $query)->sum('total');
        $returns = $query->paginate(25)->withQueryString();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('erp.purchase-payment-management.returns', compact('returns', 'suppliers', 'totalReturned'));
    }

    public function store(Request $request, Purchase $purchase)
    {
        $request->validate([
            'return_date' => 'required|date',
            'reason' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
        ]);

        $lines = [];
        foreach ($request->items as $row) {
            $qty = (float) ($row['quantity'] ?? 0);
            if ($qty > 0) {
                $lines[] = [
                    'purchase_item_id' => (int) $row['purchase_item_id'],
                    'quantity' => $qty,
                ];
            }
        }

        try {
            $return = $this->returnService->createReturn(
                $purchase,
                $lines,
                $request->return_date,
                $request->reason,
                auth()->id()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Could not record return. ' . $e->getMessage());
        }

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Return ' . $return->return_no . ' recorded. Stock updated.');
    }
}
