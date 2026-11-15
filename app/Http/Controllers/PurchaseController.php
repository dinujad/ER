<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialStockMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Services\AccountingService;
use App\Services\MaterialStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseController extends Controller
{
    public function __construct(
        protected MaterialStockService $stockService
    ) {}

    public function index(Request $request)
    {
        $query = $this->filteredPurchasesQuery($request);

        if ($request->get('export') === 'csv') {
            return $this->exportPurchasesIndexCsv($query);
        }

        $purchases = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'code']);

        return view('erp.purchases.index', compact('purchases', 'suppliers'));
    }

    private function filteredPurchasesQuery(Request $request)
    {
        $query = Purchase::with(['supplier', 'creator', 'payments', 'items:id,purchase_id,quantity,quantity_returned'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $query->where('status', Purchase::STATUS_CONFIRMED);
            if ($request->payment_status === 'paid') {
                $query->whereRaw('(purchases.paid_amount + COALESCE((SELECT SUM(amount) FROM purchase_payments WHERE purchase_id = purchases.id), 0)) >= purchases.total');
            } elseif ($request->payment_status === 'partial') {
                $query->whereRaw('(purchases.paid_amount + COALESCE((SELECT SUM(amount) FROM purchase_payments WHERE purchase_id = purchases.id), 0)) > 0')
                    ->whereRaw('(purchases.paid_amount + COALESCE((SELECT SUM(amount) FROM purchase_payments WHERE purchase_id = purchases.id), 0)) < purchases.total');
            } else {
                $query->whereRaw('(purchases.paid_amount + COALESCE((SELECT SUM(amount) FROM purchase_payments WHERE purchase_id = purchases.id), 0)) = 0')
                    ->where('purchases.total', '>', 0);
            }
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('purchase_no', 'like', "%{$s}%")
                    ->orWhere('reference_no', 'like', "%{$s}%")
                    ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
            });
        }

        return $query;
    }

    private function exportPurchasesIndexCsv($query): StreamedResponse
    {
        $purchases = (clone $query)->limit(10000)->get();
        $filename = 'purchases-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($purchases) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Date', 'Purchase No', 'Supplier', 'Code', 'Status', 'Type', 'Total (Rs)', 'Paid (Rs)', 'Balance (Rs)', 'Reference']);
            foreach ($purchases as $p) {
                fputcsv($out, [
                    $p->date?->format('Y-m-d'),
                    $p->purchase_no,
                    $p->supplier?->name ?? '',
                    $p->supplier?->code ?? '',
                    $p->status,
                    $p->purchase_type ?? '',
                    $p->total,
                    $p->total_paid,
                    $p->balance,
                    $p->reference_no ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $materials = Material::orderBy('name')->get(['id', 'name', 'unit', 'unit_price', 'type']);
        $preselectedSupplier = $request->filled('supplier_id') ? Supplier::find($request->supplier_id) : null;
        return view('erp.purchases.create', compact('suppliers', 'materials', 'preselectedSupplier'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_type' => 'required|in:cash,credit',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.buy_price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:amount,percent',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'nullable|numeric|min:0.01',
            'payments.*.payment_method' => 'nullable|in:cash,cheque,bank_transfer',
            'payments.*.reference' => 'nullable|string|max:100',
            'payments.*.cheque_number' => 'nullable|string|max:100',
            'payments.*.cheque_date' => 'nullable|date',
            'payments.*.cheque_bank' => 'nullable|string|max:100',
            'confirm' => 'nullable|in:0,1',
        ]);

        $supplier = Supplier::findOrFail($request->supplier_id);
        $items = collect($request->items)->filter(fn ($i) => ($i['quantity'] ?? 0) > 0 && ($i['buy_price'] ?? 0) >= 0);
        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one item with quantity and buy price.');
        }

        $subtotal = 0;
        foreach ($items as $row) {
            $qty = (float) $row['quantity'];
            $price = (float) $row['buy_price'];
            $disc = (float) ($row['discount'] ?? 0);
            $tax = (float) ($row['tax_amount'] ?? 0);
            $lineTotal = $qty * $price - $disc + $tax;
            $subtotal += $lineTotal;
        }

        $discountAmount = (float) ($request->discount_amount ?? 0);
        if (($request->discount_type ?? '') === 'percent' && $subtotal > 0) {
            $discountAmount = $subtotal * ($discountAmount / 100);
        }
        $taxAmount = (float) ($request->tax_amount ?? 0);
        $shipping = (float) ($request->shipping_cost ?? 0);
        $total = round($subtotal - $discountAmount + $taxAmount + $shipping, 2);
        $payments = $this->collectPayments($request);
        if ($err = $this->validatePaymentTotals($payments, $total, $request->purchase_type)) {
            return back()->withInput()->with('error', $err);
        }

        DB::beginTransaction();
        try {
            Purchase::lockPurchaseNumberGeneration();

            $purchase = Purchase::create([
                'purchase_no' => Purchase::generatePurchaseNo(),
                'date' => $request->date,
                'supplier_id' => $supplier->id,
                'purchase_type' => $request->purchase_type,
                'reference_no' => $request->reference_no,
                'notes' => $request->notes,
                'status' => $request->boolean('confirm') ? Purchase::STATUS_CONFIRMED : Purchase::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $request->discount_type,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shipping,
                'total' => $total,
                'paid_amount' => 0,
                'payment_method' => null,
                'payment_reference' => null,
                'created_by' => auth()->id(),
                'confirmed_at' => $request->boolean('confirm') ? now() : null,
            ]);
            $this->syncPurchasePayments($purchase, $payments, $request->date);

            foreach ($items as $row) {
                $qty = (float) $row['quantity'];
                $price = (float) $row['buy_price'];
                $disc = (float) ($row['discount'] ?? 0);
                $tax = (float) ($row['tax_amount'] ?? 0);
                $lineTotal = round($qty * $price - $disc + $tax, 2);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'material_id' => $row['material_id'],
                    'unit' => $row['unit'] ?? null,
                    'quantity' => $qty,
                    'buy_price' => $price,
                    'sell_price' => $row['sell_price'] ?? null,
                    'discount' => $disc,
                    'tax_amount' => $tax,
                    'line_total' => $lineTotal,
                ]);
            }

            if ($request->boolean('confirm')) {
                $this->applyPurchaseStock($purchase);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to save: ' . $e->getMessage());
        }

        $msg = $request->boolean('confirm') ? 'Purchase confirmed and stock updated.' : 'Purchase saved as draft.';
        return redirect()->route('purchases.show', $purchase)->with('success', $msg);
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.material', 'payments', 'returns.items.material', 'creator']);
        return view('erp.purchases.show', compact('purchase'));
    }

    public function report(Purchase $purchase)
    {
        if ($purchase->isCancelled()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Cancelled purchases are not available for printing.');
        }

        $purchase->load(['supplier', 'items.material', 'payments', 'creator']);
        $paymentLines = $this->paymentLinesForReport($purchase);

        return view('erp.purchases.report', compact('purchase', 'paymentLines'));
    }

    public function edit(Purchase $purchase)
    {
        if ($purchase->status !== Purchase::STATUS_DRAFT) {
            return redirect()->route('purchases.show', $purchase)->with('error', 'Only draft purchases can be edited.');
        }
        $purchase->load(['items.material', 'payments']);
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $materials = Material::orderBy('name')->get(['id', 'name', 'unit', 'unit_price', 'type']);
        $paymentRows = $this->paymentRowsForForm($purchase);
        return view('erp.purchases.edit', compact('purchase', 'suppliers', 'materials', 'paymentRows'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== Purchase::STATUS_DRAFT) {
            return back()->with('error', 'Only draft purchases can be edited.');
        }
        $request->validate([
            'date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_type' => 'required|in:cash,credit',
            'reference_no' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.buy_price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:amount,percent',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'nullable|numeric|min:0.01',
            'payments.*.payment_method' => 'nullable|in:cash,cheque,bank_transfer',
            'payments.*.reference' => 'nullable|string|max:100',
            'payments.*.cheque_number' => 'nullable|string|max:100',
            'payments.*.cheque_date' => 'nullable|date',
            'payments.*.cheque_bank' => 'nullable|string|max:100',
        ]);

        $items = collect($request->items)->filter(fn ($i) => ($i['quantity'] ?? 0) > 0 && ($i['buy_price'] ?? 0) >= 0);
        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one item.');
        }

        $subtotal = 0;
        foreach ($items as $row) {
            $qty = (float) $row['quantity'];
            $price = (float) $row['buy_price'];
            $disc = (float) ($row['discount'] ?? 0);
            $tax = (float) ($row['tax_amount'] ?? 0);
            $subtotal += $qty * $price - $disc + $tax;
        }
        $discountAmount = (float) ($request->discount_amount ?? 0);
        if (($request->discount_type ?? '') === 'percent' && $subtotal > 0) {
            $discountAmount = $subtotal * ($discountAmount / 100);
        }
        $total = round($subtotal - $discountAmount + (float)($request->tax_amount ?? 0) + (float)($request->shipping_cost ?? 0), 2);
        $payments = $this->collectPayments($request);
        if ($err = $this->validatePaymentTotals($payments, $total, $request->purchase_type)) {
            return back()->withInput()->with('error', $err);
        }
        DB::beginTransaction();
        try {
            $purchase->update([
                'date' => $request->date,
                'supplier_id' => $request->supplier_id,
                'purchase_type' => $request->purchase_type,
                'reference_no' => $request->reference_no,
                'notes' => $request->notes,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_type' => $request->discount_type,
                'tax_amount' => (float)($request->tax_amount ?? 0),
                'shipping_cost' => (float)($request->shipping_cost ?? 0),
                'total' => $total,
            ]);
            $this->syncPurchasePayments($purchase, $payments, $request->date);
            $purchase->items()->delete();
            foreach ($items as $row) {
                $qty = (float) $row['quantity'];
                $price = (float) $row['buy_price'];
                $disc = (float) ($row['discount'] ?? 0);
                $tax = (float) ($row['tax_amount'] ?? 0);
                $lineTotal = round($qty * $price - $disc + $tax, 2);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'material_id' => $row['material_id'],
                    'unit' => $row['unit'] ?? null,
                    'quantity' => $qty,
                    'buy_price' => $price,
                    'sell_price' => $row['sell_price'] ?? null,
                    'discount' => $disc,
                    'tax_amount' => $tax,
                    'line_total' => $lineTotal,
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        }
        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase updated.');
    }

    public function confirm(Purchase $purchase)
    {
        if ($purchase->status !== Purchase::STATUS_DRAFT) {
            return back()->with('error', 'Only draft purchases can be confirmed.');
        }
        if ($purchase->purchase_type === Purchase::TYPE_CASH && $purchase->balance > 0.009) {
            return back()->with('error', 'Full payment purchase must be fully paid before confirming.');
        }
        DB::beginTransaction();
        try {
            $this->applyPurchaseStock($purchase);
            $purchase->update(['status' => Purchase::STATUS_CONFIRMED, 'confirmed_at' => now()]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to confirm: ' . $e->getMessage());
        }
        return back()->with('success', 'Purchase confirmed. Stock updated.');
    }

    public function cancel(Request $request, Purchase $purchase)
    {
        if ($purchase->status === Purchase::STATUS_CANCELLED) {
            return back()->with('error', 'Purchase is already cancelled.');
        }
        $request->validate(['cancel_reason' => 'required|string|max:500']);

        $wasConfirmed = $purchase->status === Purchase::STATUS_CONFIRMED;

        DB::beginTransaction();
        try {
            if ($wasConfirmed) {
                $this->reversePurchaseStock($purchase);
            }
            $purchase->update([
                'status' => Purchase::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $request->cancel_reason,
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to cancel: ' . $e->getMessage());
        }

        $msg = $wasConfirmed
            ? 'Purchase cancelled. Stock has been reversed and it will no longer appear in reports.'
            : 'Purchase cancelled.';

        return back()->with('success', $msg);
    }

    protected function applyPurchaseStock(Purchase $purchase): void
    {
        $purchase->load('items.material');
        foreach ($purchase->items as $item) {
            $material = $item->material;
            if (!$material) {
                throw new \RuntimeException('Cannot update stock: material missing on a purchase line.');
            }

            $qty = (float) $item->quantity;
            if ($qty <= 0) {
                continue;
            }

            $this->stockService->applyMovement(
                $material,
                $qty,
                MaterialStockMovement::TYPE_PURCHASE_IN,
                Purchase::class,
                $purchase->id,
                $purchase->purchase_no,
                'Purchase confirmed',
                auth()->id(),
                false
            );

            $material->refresh()->update(['unit_price' => $item->buy_price]);
        }
    }

    protected function reversePurchaseStock(Purchase $purchase): void
    {
        $purchase->load('items.material');

        foreach ($purchase->items as $item) {
            $qty = round((float) $item->quantity - (float) $item->quantity_returned, 2);
            if ($qty <= 0) {
                continue;
            }

            $material = $item->material;
            if (!$material) {
                throw new \RuntimeException('Cannot reverse stock: material missing on a purchase line.');
            }

            $this->stockService->applyMovement(
                $material,
                -$qty,
                MaterialStockMovement::TYPE_PURCHASE_CANCEL,
                Purchase::class,
                $purchase->id,
                $purchase->purchase_no,
                'Purchase cancelled (remaining stock after returns)',
                auth()->id(),
                true
            );
        }
    }

    protected function collectPayments(Request $request): Collection
    {
        return collect($request->input('payments', []))
            ->map(fn ($p) => $this->normalizePaymentRow($p))
            ->filter(fn ($p) => $p['amount'] > 0);
    }

    protected function normalizePaymentRow(array $p): array
    {
        $method = $p['payment_method'] ?? null;
        $isCheque = $method === 'cheque';

        return [
            'amount' => round((float) ($p['amount'] ?? 0), 2),
            'payment_method' => $method,
            'reference' => $isCheque ? null : (trim((string) ($p['reference'] ?? '')) ?: null),
            'cheque_number' => $isCheque ? (trim((string) ($p['cheque_number'] ?? '')) ?: null) : null,
            'cheque_date' => $isCheque ? ($p['cheque_date'] ?? null) : null,
            'cheque_bank' => $isCheque ? (trim((string) ($p['cheque_bank'] ?? '')) ?: null) : null,
        ];
    }

    protected function validatePaymentTotals(Collection $payments, float $total, string $purchaseType): ?string
    {
        $paid = round($payments->sum('amount'), 2);
        if ($paid > $total) {
            return 'Total payments cannot exceed grand total.';
        }
        if ($purchaseType === Purchase::TYPE_CASH && $total > 0 && abs($paid - $total) > 0.009) {
            return 'Full payment purchases: payment total must equal grand total.';
        }
        foreach ($payments as $p) {
            if (empty($p['payment_method'])) {
                return 'Select a payment method for each payment amount.';
            }
            if ($p['payment_method'] === 'cheque') {
                if (empty($p['cheque_number'])) {
                    return 'Cheque number is required for cheque payments.';
                }
                if (empty($p['cheque_date'])) {
                    return 'Cheque date is required for cheque payments.';
                }
                if (empty($p['cheque_bank'])) {
                    return 'Issued bank is required for cheque payments.';
                }
            }
        }

        return null;
    }

    protected function syncPurchasePayments(Purchase $purchase, Collection $payments, $paymentDate): void
    {
        $purchase->payments()->pluck('id')->each(
            fn ($id) => AccountingService::reverseEvent("purchase-payment:{$id}:entry")
        );
        $purchase->payments()->delete();
        $items = $payments->values()->all();
        $paid = round($payments->sum('amount'), 2);

        $date = $paymentDate instanceof \DateTimeInterface
            ? $paymentDate->format('Y-m-d')
            : (string) $paymentDate;

        if (count($items) === 0) {
            $purchase->update([
                'paid_amount' => 0,
                'payment_method' => null,
                'payment_reference' => null,
                'cheque_date' => null,
                'cheque_bank' => null,
                'cheque_number' => null,
            ]);

            return;
        }

        $first = $items[0];
        $purchase->update(array_merge(
            $this->purchasePaymentAttributes($first),
            ['paid_amount' => $paid]
        ));

        foreach ($items as $item) {
            PurchasePayment::create(array_merge(
                $this->purchasePaymentRecordAttributes($item),
                [
                    'supplier_id' => $purchase->supplier_id,
                    'purchase_id' => $purchase->id,
                    'entry_type' => PurchasePayment::ENTRY_PAYMENT,
                    'payment_date' => $date,
                    'created_by' => auth()->id(),
                ]
            ));
        }
    }

    protected function purchasePaymentAttributes(array $p): array
    {
        $attrs = [
            'paid_amount' => $p['amount'],
            'payment_method' => $p['payment_method'],
            'payment_reference' => $p['reference'],
            'cheque_date' => null,
            'cheque_bank' => null,
            'cheque_number' => null,
        ];
        if ($p['payment_method'] === 'cheque') {
            $attrs['cheque_date'] = $p['cheque_date'];
            $attrs['cheque_bank'] = $p['cheque_bank'];
            $attrs['cheque_number'] = $p['cheque_number'];
            $attrs['payment_reference'] = null;
        }

        return $attrs;
    }

    protected function purchasePaymentRecordAttributes(array $p): array
    {
        return [
            'amount' => $p['amount'],
            'payment_method' => $p['payment_method'],
            'reference' => $p['reference'],
            'cheque_date' => $p['cheque_date'],
            'cheque_bank' => $p['cheque_bank'],
            'cheque_number' => $p['cheque_number'],
        ];
    }

    protected function paymentRowsForForm(Purchase $purchase): array
    {
        $rows = [];
        if ($purchase->payments->isNotEmpty()) {
            foreach ($purchase->payments as $pay) {
                if ($pay->isRefund()) {
                    continue;
                }
                $rows[] = [
                    'amount' => $pay->amount,
                    'payment_method' => $pay->payment_method,
                    'reference' => $pay->reference,
                    'cheque_number' => $pay->cheque_number,
                    'cheque_date' => $pay->cheque_date,
                    'cheque_bank' => $pay->cheque_bank,
                ];
            }

            return $rows;
        }

        if ((float) $purchase->paid_amount > 0) {
            $rows[] = [
                'amount' => $purchase->paid_amount,
                'payment_method' => $purchase->payment_method,
                'reference' => $purchase->payment_reference,
                'cheque_number' => $purchase->cheque_number,
                'cheque_date' => $purchase->cheque_date,
                'cheque_bank' => $purchase->cheque_bank,
            ];
        }
        foreach ($purchase->payments as $pay) {
            $rows[] = [
                'amount' => $pay->amount,
                'payment_method' => $pay->payment_method,
                'reference' => $pay->reference,
                'cheque_number' => $pay->cheque_number,
                'cheque_date' => $pay->cheque_date,
                'cheque_bank' => $pay->cheque_bank,
            ];
        }

        return $rows ?: [['amount' => '', 'payment_method' => '', 'reference' => '', 'cheque_number' => '', 'cheque_date' => '', 'cheque_bank' => '']];
    }

    protected function paymentLinesForReport(Purchase $purchase): array
    {
        $lines = [];
        $paymentRows = $purchase->payments->filter(fn ($pay) => ! $pay->isRefund());

        if ($paymentRows->isNotEmpty()) {
            foreach ($paymentRows as $pay) {
                $lines[] = [
                    'date' => $pay->payment_date,
                    'method' => $pay->payment_method,
                    'reference' => $pay->reference,
                    'cheque_number' => $pay->cheque_number,
                    'cheque_date' => $pay->cheque_date,
                    'cheque_bank' => $pay->cheque_bank,
                    'amount' => (float) $pay->amount,
                ];
            }

            return $lines;
        }

        if ((float) $purchase->paid_amount > 0) {
            $lines[] = [
                'date' => $purchase->date,
                'method' => $purchase->payment_method,
                'reference' => $purchase->payment_reference,
                'cheque_number' => $purchase->cheque_number,
                'cheque_date' => $purchase->cheque_date,
                'cheque_bank' => $purchase->cheque_bank,
                'amount' => (float) $purchase->paid_amount,
            ];
        }

        return $lines;
    }
}
