<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Services\AccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query()->withCount(['purchases' => fn ($q) => $q->where('status', 'confirmed')]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('company_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('outstanding') && $request->outstanding === 'yes') {
            $query->where(function ($q) {
                $q->where('opening_balance', '>', 0)
                    ->orWhereHas('purchases', function ($p) {
                        $p->where('status', Purchase::STATUS_CONFIRMED)
                            ->whereRaw(
                                'purchases.total > purchases.paid_amount + COALESCE((SELECT SUM(amount) FROM purchase_payments WHERE purchase_id = purchases.id), 0)'
                            );
                    });
            });
        }

        $suppliers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('erp.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('erp.suppliers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'tax_vat_no' => 'nullable|string|max:100',
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'bank_account_name' => 'nullable|string|max:120',
            'bank_account_no' => 'nullable|string|max:40',
            'bank_name' => 'nullable|string|max:80',
            'bank_code' => 'nullable|string|max:10',
            'branch_code' => 'nullable|string|max:10',
            'opening_balance' => 'nullable|numeric',
            'status' => 'in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $data = $request->only([
            'name', 'company_name', 'phone', 'email', 'address', 'tax_vat_no', 'status', 'notes', 'payment_terms_days', 'bank_account_name', 'bank_account_no', 'bank_name', 'bank_code', 'branch_code',
        ]);
        $data['opening_balance'] = $request->input('opening_balance', 0);
        $data['code'] = Supplier::generateCode();

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully.');
    }

    public function show(Request $request, Supplier $supplier)
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $purchasesQuery = $this->purchasesInRangeQuery($supplier, $dateFrom, $dateTo)
            ->with(['items.material']);

        $purchases = (clone $purchasesQuery)->paginate(25)->withQueryString();

        $purchasesForSummary = (clone $purchasesQuery)->get();
        $periodSummary = [
            'count' => $purchasesForSummary->count(),
            'total' => round($purchasesForSummary->sum('total'), 2),
            'paid' => round($purchasesForSummary->sum(fn ($p) => $p->total_paid), 2),
            'balance' => round($purchasesForSummary->sum(fn ($p) => $p->balance), 2),
        ];

        $periodPayments = PurchasePayment::query()
            ->where('supplier_id', $supplier->id)
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo)
            ->with('purchase')
            ->orderByDesc('payment_date')
            ->get();

        $periodPaymentsTotal = round((float) $periodPayments->sum('amount'), 2);

        return view('erp.suppliers.show', compact(
            'supplier',
            'purchases',
            'dateFrom',
            'dateTo',
            'periodSummary',
            'periodPayments',
            'periodPaymentsTotal'
        ));
    }

    public function exportPurchases(Request $request, Supplier $supplier): StreamedResponse|\Illuminate\Http\Response
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $format = $request->get('format', 'csv');

        $purchases = $this->purchasesInRangeQuery($supplier, $dateFrom, $dateTo)
            ->with(['items.material'])
            ->orderBy('date')
            ->get();

        $periodSummary = [
            'count' => $purchases->count(),
            'total' => round($purchases->sum('total'), 2),
            'paid' => round($purchases->sum(fn ($p) => $p->total_paid), 2),
            'balance' => round($purchases->sum(fn ($p) => $p->balance), 2),
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('erp.suppliers.purchase_report_pdf', compact(
                'supplier',
                'purchases',
                'dateFrom',
                'dateTo',
                'periodSummary'
            ))->setPaper('a4', 'landscape');

            $filename = $this->exportFilename($supplier, $dateFrom, $dateTo, 'pdf');

            return $pdf->download($filename);
        }

        $filename = $this->exportFilename($supplier, $dateFrom, $dateTo, 'csv');

        return response()->streamDownload(function () use ($supplier, $purchases, $dateFrom, $dateTo, $periodSummary) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, ['Supplier Purchase Report']);
            fputcsv($out, ['Supplier', $supplier->name]);
            fputcsv($out, ['Code', $supplier->code ?? '']);
            fputcsv($out, ['Company', $supplier->company_name ?? '']);
            fputcsv($out, ['Phone', $supplier->phone ?? '']);
            fputcsv($out, ['Period', $dateFrom . ' to ' . $dateTo]);
            fputcsv($out, ['Generated', now()->format('Y-m-d H:i')]);
            fputcsv($out, []);

            fputcsv($out, [
                'Purchase No', 'Date', 'Reference', 'Type', 'Status',
                'Subtotal (Rs)', 'Discount (Rs)', 'Tax (Rs)', 'Shipping (Rs)', 'Total (Rs)',
                'Paid (Rs)', 'Balance (Rs)',
            ]);

            foreach ($purchases as $p) {
                fputcsv($out, [
                    $p->purchase_no,
                    $p->date?->format('Y-m-d'),
                    $p->reference_no ?? '',
                    $p->purchase_type_label ?? $p->purchase_type,
                    $p->status,
                    $p->subtotal,
                    $p->discount_amount,
                    $p->tax_amount,
                    $p->shipping_cost,
                    $p->total,
                    $p->total_paid,
                    $p->balance,
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Summary', 'Count', 'Total (Rs)', 'Paid (Rs)', 'Balance (Rs)']);
            fputcsv($out, [
                'Period totals',
                $periodSummary['count'],
                $periodSummary['total'],
                $periodSummary['paid'],
                $periodSummary['balance'],
            ]);

            fputcsv($out, []);
            fputcsv($out, ['Line items']);
            fputcsv($out, ['Purchase No', 'Date', 'Material', 'Qty', 'Unit', 'Buy Price (Rs)', 'Line Total (Rs)']);

            foreach ($purchases as $p) {
                foreach ($p->items as $item) {
                    fputcsv($out, [
                        $p->purchase_no,
                        $p->date?->format('Y-m-d'),
                        $item->material?->name ?? '—',
                        $item->quantity,
                        $item->unit ?? '',
                        $item->buy_price,
                        $item->line_total,
                    ]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function edit(Supplier $supplier)
    {
        return view('erp.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'tax_vat_no' => 'nullable|string|max:100',
            'payment_terms_days' => 'nullable|integer|min:0|max:365',
            'bank_account_name' => 'nullable|string|max:120',
            'bank_account_no' => 'nullable|string|max:40',
            'bank_name' => 'nullable|string|max:80',
            'bank_code' => 'nullable|string|max:10',
            'branch_code' => 'nullable|string|max:10',
            'opening_balance' => 'nullable|numeric',
            'status' => 'in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($request->only([
            'name', 'company_name', 'phone', 'email', 'address', 'tax_vat_no', 'opening_balance', 'status', 'notes', 'payment_terms_days', 'bank_account_name', 'bank_account_no', 'bank_name', 'bank_code', 'branch_code',
        ]));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->where('status', 'confirmed')->exists()) {
            return back()->with('error', 'Cannot delete supplier with confirmed purchases.');
        }
        $supplier->purchases()->delete();
        $supplier->payments()->pluck('id')->each(
            fn ($id) => AccountingService::reverseEvent("purchase-payment:{$id}:entry")
        );
        $supplier->payments()->delete();
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    /** @return array{0: string, 1: string} */
    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [$dateFrom, $dateTo];
    }

    private function purchasesInRangeQuery(Supplier $supplier, string $dateFrom, string $dateTo)
    {
        return Purchase::query()
            ->where('supplier_id', $supplier->id)
            ->where('status', Purchase::STATUS_CONFIRMED)
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    private function exportFilename(Supplier $supplier, string $from, string $to, string $ext): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($supplier->name));
        $slug = trim($slug, '-') ?: 'supplier';

        return "supplier-{$slug}-purchases-{$from}-to-{$to}.{$ext}";
    }
}
