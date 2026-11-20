<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.6 — one search box for invoices, bills, customers, suppliers, payments, credit notes and journals
 * (by number, name, phone, TIN, cheque number, reference or exact amount).
 */
class GlobalSearchController extends Controller
{
    private const LIMIT = 15;

    public function index(Request $request)
    {
        $validated = $request->validate(['q' => 'nullable|string|max:80']);
        $q = trim((string) ($validated['q'] ?? ''));
        $groups = [];

        if (mb_strlen($q) >= 2) {
            $like = '%' . $q . '%';
            $amount = is_numeric(str_replace(',', '', $q)) ? round((float) str_replace(',', '', $q), 2) : null;

            $groups['Invoices'] = $this->rows('orders', fn ($b) => $b
                ->where(fn ($w) => $w->where('invoice_number', 'like', $like)->orWhere('order_number', 'like', $like)
                    ->when($amount !== null, fn ($x) => $x->orWhereRaw('ABS(total_amount - ?) < 0.01', [$amount])
                        ->orWhereRaw('ABS(' . \App\Models\Order::invoiceTotalSql() . ' - ?) < 0.01', [$amount]))),
                fn ($r) => [
                    'title' => ($r->invoice_number ?: 'Order ' . $r->order_number),
                    'detail' => 'Order ' . $r->order_number . ' · Rs ' . number_format((float) $r->total_amount, 2) . ' · ' . $r->status,
                    'url' => url('accounting/phase6/invoices/' . $r->id),
                ]);

            $groups['Customers'] = $this->rows('customers', fn ($b) => $b
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('business_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)->orWhere('tin', 'like', $like)),
                fn ($r) => [
                    'title' => $r->business_name ?: $r->name,
                    'detail' => trim(($r->phone ?? '') . ($r->tin ? ' · TIN ' . $r->tin : '')),
                    'url' => Route::has('erp.customers.show') ? route('erp.customers.show', $r->id) : null,
                ]);

            $groups['Bills'] = $this->rows('purchases', fn ($b) => $b
                ->where(fn ($w) => $w->where('purchase_no', 'like', $like)->orWhere('reference_no', 'like', $like)
                    ->when($amount !== null, fn ($x) => $x->orWhereRaw('ABS(total - ?) < 0.01', [$amount]))),
                fn ($r) => [
                    'title' => 'Bill ' . $r->purchase_no,
                    'detail' => ($r->reference_no ? 'Ref ' . $r->reference_no . ' · ' : '') . 'Rs ' . number_format((float) $r->total, 2) . ' · ' . $r->status,
                    'url' => url('accounting/phase6/bills/' . $r->id),
                ]);

            $groups['Suppliers'] = $this->rows('suppliers', fn ($b) => $b
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('company_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)->orWhere('tax_vat_no', 'like', $like)),
                fn ($r) => [
                    'title' => $r->company_name ?: $r->name,
                    'detail' => trim(($r->phone ?? '') . ($r->tax_vat_no ? ' · VAT ' . $r->tax_vat_no : '')),
                    'url' => Route::has('suppliers.show') ? route('suppliers.show', $r->id) : null,
                ]);

            $groups['Customer payments'] = $this->rows('payments', fn ($b) => $b
                ->where(fn ($w) => $w->where('reference_number', 'like', $like)
                    ->when(Schema::hasColumn('payments', 'cheque_number'), fn ($x) => $x->orWhere('cheque_number', 'like', $like))
                    ->when($amount !== null, fn ($x) => $x->orWhereRaw('ABS(amount - ?) < 0.01', [$amount]))),
                fn ($r) => [
                    'title' => 'Payment #' . $r->id . ' · Rs ' . number_format((float) $r->amount, 2),
                    'detail' => ucfirst((string) $r->payment_method) . ' · ' . ($r->payment_date ?? '') . ($r->reference_number ? ' · ' . $r->reference_number : ''),
                    'url' => $r->order_id ? url('accounting/phase6/invoices/' . $r->order_id) . '#payments' : null,
                ]);

            $groups['Supplier payments'] = $this->rows('purchase_payments', fn ($b) => $b
                ->where(fn ($w) => $w->where('reference', 'like', $like)->orWhere('cheque_number', 'like', $like)
                    ->when($amount !== null, fn ($x) => $x->orWhereRaw('ABS(amount - ?) < 0.01', [$amount]))),
                fn ($r) => [
                    'title' => 'Supplier payment #' . $r->id . ' · Rs ' . number_format((float) $r->amount, 2),
                    'detail' => ucfirst(str_replace('_', ' ', (string) $r->payment_method)) . ' · ' . ($r->payment_date ?? ''),
                    'url' => $r->purchase_id ? url('accounting/phase6/bills/' . $r->purchase_id) . '#payments' : null,
                ]);

            $groups['Credit notes'] = $this->rows('credit_notes', fn ($b) => $b
                ->where(fn ($w) => $w->where('credit_note_number', 'like', $like)->orWhere('reason', 'like', $like)),
                fn ($r) => [
                    'title' => $r->credit_note_number,
                    'detail' => 'Rs ' . number_format((float) $r->total, 2) . ' · ' . $r->reason,
                    'url' => url('accounting/phase6/invoices/' . $r->order_id) . '#credit-notes',
                ]);

            $groups['Journals'] = Schema::hasTable('journal_entries')
                ? DB::table('journal_entries')
                    ->where(fn ($w) => $w->where('transaction_number', 'like', $like)->orWhere('reference', 'like', $like)->orWhere('name', 'like', $like))
                    ->select('transaction_number', DB::raw('MIN(date) as date'), DB::raw('MIN(name) as name'), DB::raw('SUM(debit) as total'))
                    ->groupBy('transaction_number')->orderByDesc(DB::raw('MIN(date)'))->limit(self::LIMIT)->get()
                    ->map(fn ($r) => [
                        'title' => $r->transaction_number,
                        'detail' => $r->date . ' · ' . $r->name . ' · Rs ' . number_format((float) $r->total, 2),
                        'url' => url('accounting/phase6/journal/' . urlencode($r->transaction_number)),
                    ])->all()
                : [];

            $groups = array_filter($groups);
        }

        return view('accounting::phase6.search.index', compact('q', 'groups'));
    }

    private function rows(string $table, callable $where, callable $map): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return $where(DB::table($table))->orderByDesc('id')->limit(self::LIMIT)->get()->map($map)->all();
    }
}
