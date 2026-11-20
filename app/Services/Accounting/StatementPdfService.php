<?php

namespace App\Services\Accounting;

use App\Models\Accounting\BankStatementLine;
use App\Services\AccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class StatementPdfService
{
    public static function customerStatement(int $customerId, string $from, string $to)
    {
        if (! Schema::hasTable('orders')) {
            throw new RuntimeException('Orders unavailable.');
        }

        $customer = Schema::hasTable('customers')
            ? DB::table('customers')->where('id', $customerId)->first()
            : null;
        if (! $customer) {
            throw new RuntimeException('Customer not found.');
        }

        $orders = DB::table('orders')
            ->where('customer_id', $customerId)
            ->whereDate(Schema::hasColumn('orders', 'order_date') ? 'order_date' : 'created_at', '>=', $from)
            ->whereDate(Schema::hasColumn('orders', 'order_date') ? 'order_date' : 'created_at', '<=', $to)
            ->orderBy('id')
            ->get();

        $payments = Schema::hasTable('payments')
            ? DB::table('payments')
                ->join('orders', 'orders.id', '=', 'payments.order_id')
                ->where('orders.customer_id', $customerId)
                ->whereDate('payments.payment_date', '>=', $from)
                ->whereDate('payments.payment_date', '<=', $to)
                ->select('payments.*')
                ->orderBy('payments.id')
                ->get()
            : collect();

        $invoiceTotal = round((float) $orders->sum(fn ($o) => self::billedTotal($o)), 2);
        $paidTotal = round((float) $payments->sum(fn ($p) => (float) ($p->amount ?? 0)), 2);

        $opening = self::customerBalanceBefore($customerId, $from);

        $data = [
            'title' => 'Customer Statement',
            'currency' => 'LKR',
            'party' => $customer,
            'from' => $from,
            'to' => $to,
            'generated_at' => now()->format('Y-m-d H:i'),
            'opening' => number_format($opening, 2, '.', ''),
            'invoices' => $orders,
            'payments' => $payments,
            'invoice_total' => number_format($invoiceTotal, 2, '.', ''),
            'paid_total' => number_format($paidTotal, 2, '.', ''),
            'closing' => number_format(round($opening + $invoiceTotal - $paidTotal, 2), 2, '.', ''),
        ];

        return Pdf::loadView('accounting::phase2.pdf.customer_statement', $data)->setPaper('a4');
    }

    public static function supplierStatement(int $supplierId, string $from, string $to)
    {
        $supplier = Schema::hasTable('suppliers')
            ? DB::table('suppliers')->where('id', $supplierId)->first()
            : null;
        if (! $supplier) {
            throw new RuntimeException('Supplier not found.');
        }

        $bills = DB::table('purchases')
            ->where('supplier_id', $supplierId)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('id')
            ->get();

        $payments = Schema::hasTable('purchase_payments')
            ? DB::table('purchase_payments')
                ->where('supplier_id', $supplierId)
                ->whereDate('payment_date', '>=', $from)
                ->whereDate('payment_date', '<=', $to)
                ->orderBy('id')
                ->get()
            : collect();

        $billTotal = round((float) $bills->sum(fn ($b) => (float) ($b->total ?? 0)), 2);
        $paidTotal = round((float) $payments->sum(fn ($p) => (float) ($p->amount ?? 0)), 2);
        $opening = self::supplierBalanceBefore($supplierId, $from);

        $data = [
            'title' => 'Supplier Statement',
            'currency' => 'LKR',
            'party' => $supplier,
            'from' => $from,
            'to' => $to,
            'generated_at' => now()->format('Y-m-d H:i'),
            'opening' => number_format($opening, 2, '.', ''),
            'bills' => $bills,
            'payments' => $payments,
            'bill_total' => number_format($billTotal, 2, '.', ''),
            'paid_total' => number_format($paidTotal, 2, '.', ''),
            'closing' => number_format(round($opening + $billTotal - $paidTotal, 2), 2, '.', ''),
        ];

        return Pdf::loadView('accounting::phase2.pdf.supplier_statement', $data)->setPaper('a4');
    }

    public static function bankReconciliationReport(int $chartOfAccountId, string $from, string $to, ?float $statementEnding = null)
    {
        $account = DB::table('chart_of_accounts')->where('id', $chartOfAccountId)->first();
        if (! $account) {
            throw new RuntimeException('Bank account not found.');
        }

        $register = AccountRegisterService::register($chartOfAccountId, $from, $to);
        $matched = BankStatementLine::query()
            ->whereHas('import', fn ($q) => $q->where('chart_of_account_id', $chartOfAccountId))
            ->where('match_status', 'matched')
            ->whereBetween('txn_date', [$from, $to])
            ->orderBy('txn_date')
            ->get();

        $unmatched = BankStatementLine::query()
            ->whereHas('import', fn ($q) => $q->where('chart_of_account_id', $chartOfAccountId))
            ->where('match_status', 'unmatched')
            ->whereBetween('txn_date', [$from, $to])
            ->orderBy('txn_date')
            ->get();

        $bookBalance = $register['closing'];
        $stmtEnding = $statementEnding !== null ? number_format($statementEnding, 2, '.', '') : null;
        $difference = $stmtEnding !== null
            ? number_format(round((float) $stmtEnding - (float) $bookBalance, 2), 2, '.', '')
            : null;

        $data = [
            'title' => 'Bank Reconciliation Report',
            'currency' => 'LKR',
            'account' => $account,
            'from' => $from,
            'to' => $to,
            'generated_at' => now()->format('Y-m-d H:i'),
            'book_balance' => $bookBalance,
            'statement_ending' => $stmtEnding,
            'difference' => $difference,
            'cleared' => $matched,
            'outstanding' => $unmatched,
            'prepared_by' => auth()->user()?->name ?? auth()->id(),
        ];

        return Pdf::loadView('accounting::phase2.pdf.bank_reconciliation', $data)->setPaper('a4');
    }

    public static function arControlReconciliation(?string $asOf = null): array
    {
        $asOf = $asOf ?: date('Y-m-d');
        $arId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
        $glBalance = $arId ? AccountRegisterService::register($arId, '1970-01-01', $asOf)['closing'] : '0.00';

        $subledger = 0.0;
        $contributing = [];
        $notes = [];

        if (Schema::hasTable('orders')) {
            $orders = DB::table('orders')
                ->whereNotIn('status', ['cancelled', 'quotation', 'draft'])
                ->get();
            foreach ($orders as $o) {
                $total = self::billedTotal($o);
                $paid = 0.0;
                if (Schema::hasTable('payments')) {
                    $pq = DB::table('payments')->where('order_id', $o->id);
                    if (Schema::hasColumn('payments', 'cheque_status')) {
                        $pq->where(function ($w) {
                            $w->whereNull('cheque_status')
                                ->orWhere('cheque_status', '!=', 'bounced');
                        });
                    }
                    if (Schema::hasColumn('payments', 'entry_type')) {
                        $pq->where(function ($w) {
                            $w->whereNull('entry_type')->orWhere('entry_type', '!=', 'refund');
                        });
                    }
                    $paid = round((float) $pq->sum('amount'), 2);
                }
                $outstanding = max(0, round($total - $paid, 2));
                $subledger = round($subledger + $outstanding, 2);
                if ($outstanding > 0) {
                    $contributing[] = [
                        'order_id' => $o->id,
                        'total' => number_format($total, 2, '.', ''),
                        'paid' => number_format($paid, 2, '.', ''),
                        'outstanding' => number_format($outstanding, 2, '.', ''),
                    ];
                }
            }
        }

        $diff = round((float) $glBalance - $subledger, 2);
        if (abs($diff) > 0.009) {
            $notes[] = 'Difference may reflect timing (unposted credit sales), advances in non-AR accounts, or opening balances not mirrored in orders.';
        }

        return [
            'as_of' => $asOf,
            'business_id' => 0,
            'gl_ar' => $glBalance,
            'subledger_ar' => number_format(round($subledger, 2), 2, '.', ''),
            'difference' => number_format($diff, 2, '.', ''),
            'contributing' => $contributing,
            'notes' => $notes,
        ];
    }

    public static function apControlReconciliation(?string $asOf = null): array
    {
        $asOf = $asOf ?: date('Y-m-d');
        $apId = AccountingService::accountId(AccountingService::GL_ACCOUNTS_PAY);
        $glBalance = $apId ? AccountRegisterService::register($apId, '1970-01-01', $asOf)['closing'] : '0.00';

        $subledger = 0.0;
        $contributing = [];
        $notes = [];

        if (Schema::hasTable('purchases')) {
            $bills = DB::table('purchases')->whereNotIn('status', ['cancelled', 'draft'])->get();
            foreach ($bills as $b) {
                $total = round((float) ($b->total ?? 0), 2);
                $paid = Schema::hasTable('purchase_payments')
                    ? round((float) DB::table('purchase_payments')->where('purchase_id', $b->id)->sum('amount'), 2)
                    : round((float) ($b->paid_amount ?? 0), 2);
                $outstanding = max(0, round($total - $paid, 2));
                $subledger = round($subledger + $outstanding, 2);
                if ($outstanding > 0) {
                    $contributing[] = [
                        'purchase_id' => $b->id,
                        'total' => number_format($total, 2, '.', ''),
                        'paid' => number_format($paid, 2, '.', ''),
                        'outstanding' => number_format($outstanding, 2, '.', ''),
                    ];
                }
            }
        }

        $diff = round((float) $glBalance - $subledger, 2);
        if (abs($diff) > 0.009) {
            $notes[] = 'Difference may reflect supplier advances, unposted confirmed bills, or opening AP balances.';
        }

        return [
            'as_of' => $asOf,
            'business_id' => 0,
            'gl_ap' => $glBalance,
            'subledger_ap' => number_format(round($subledger, 2), 2, '.', ''),
            'difference' => number_format($diff, 2, '.', ''),
            'contributing' => $contributing,
            'notes' => $notes,
        ];
    }

    /** Billed amount of an orders row: goods + SSCL + VAT charged on the invoice (legacy rows: goods only). */
    protected static function billedTotal(object $o): float
    {
        return round((float) ($o->total_amount ?? $o->final_total ?? 0)
            + (float) ($o->invoice_sscl_amount ?? 0)
            + (float) ($o->invoice_vat_amount ?? 0), 2);
    }

    protected static function billedTotalSql(): string
    {
        return Schema::hasColumn('orders', 'invoice_sscl_amount')
            ? \App\Models\Order::invoiceTotalSql()
            : 'orders.total_amount';
    }

    protected static function customerBalanceBefore(int $customerId, string $before): float
    {
        $inv = 0.0;
        $paid = 0.0;
        if (Schema::hasTable('orders')) {
            $col = Schema::hasColumn('orders', 'order_date') ? 'order_date' : 'created_at';
            $inv = round((float) DB::table('orders')->where('customer_id', $customerId)->whereDate($col, '<', $before)->sum(DB::raw(self::billedTotalSql())), 2);
        }
        if (Schema::hasTable('payments')) {
            $paid = round((float) DB::table('payments')
                ->join('orders', 'orders.id', '=', 'payments.order_id')
                ->where('orders.customer_id', $customerId)
                ->whereDate('payments.payment_date', '<', $before)
                ->sum('payments.amount'), 2);
        }

        return round($inv - $paid, 2);
    }

    protected static function supplierBalanceBefore(int $supplierId, string $before): float
    {
        $bills = Schema::hasTable('purchases')
            ? round((float) DB::table('purchases')->where('supplier_id', $supplierId)->whereDate('date', '<', $before)->sum('total'), 2)
            : 0.0;
        $paid = Schema::hasTable('purchase_payments')
            ? round((float) DB::table('purchase_payments')->where('supplier_id', $supplierId)->whereDate('payment_date', '<', $before)->sum('amount'), 2)
            : 0.0;

        return round($bills - $paid, 2);
    }
}
