<?php

namespace App\Services\Accounting;

use App\Models\CreditNote;
use App\Models\Order;
use App\Models\SaleReturn;
use App\Models\StockLedger;
use App\Services\AccountingService;
use App\Services\Audit\AuditLogger;
use App\Services\SalesAccountingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Phase 6.4 — Xero-style receivables: credit notes, void invoice, payment reminders, ageing.
 */
class ReceivablesService
{
    /** Billed value an adjustment credit note may still take: what is left of the invoice after returns and credits. */
    public function creditableAmount(Order $order): float
    {
        return max(0, round($order->net_total, 2));
    }

    /**
     * Issue an adjustment credit note (price reduction / goodwill, no goods back).
     * $itemAmount is the reduction at item-price level (the same scale as the invoice lines);
     * SSCL and VAT are credited in the same proportion as on the invoice.
     */
    public function issueAdjustmentCreditNote(Order $order, float $itemAmount, string $reason, ?string $date = null): CreditNote
    {
        if (!$order->isInvoiceIssued() || $order->invoiceStatus() === 'voided') {
            throw new InvalidArgumentException('Credit notes can only be raised on an issued invoice.');
        }
        $itemAmount = round($itemAmount, 2);
        if ($itemAmount <= 0) {
            throw new InvalidArgumentException('Credit amount must be greater than zero.');
        }
        [$goods, $shares, $total] = $this->creditSplit($order, $itemAmount);
        if ($total - $this->creditableAmount($order) > 0.009) {
            throw new InvalidArgumentException('Credit of Rs ' . number_format($total, 2) . ' is more than what is left on this invoice (Rs ' . number_format($this->creditableAmount($order), 2) . ').');
        }

        $date = $date ?: now()->toDateString();

        return DB::transaction(function () use ($order, $goods, $shares, $total, $reason, $date) {
            $cn = CreditNote::query()->create([
                'credit_note_number' => CreditNote::nextNumber(),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'type' => CreditNote::TYPE_ADJUSTMENT,
                'credit_note_date' => $date,
                'reason' => mb_substr($reason, 0, 255),
                'goods_amount' => $goods,
                'sscl_amount' => $shares['sscl'],
                'vat_amount' => $shares['vat'],
                'total' => $total,
                'status' => CreditNote::STATUS_ISSUED,
                'created_by' => auth()->id(),
            ]);
            $this->postCreditNote($cn, $order);
            AuditLogger::record($order, 'credit_note_issued', [], [
                'credit_note' => $cn->credit_note_number,
                'goods' => $goods,
                'sscl' => $shares['sscl'],
                'vat' => $shares['vat'],
                'total' => $total,
            ], $reason);

            return $cn;
        });
    }

    /**
     * Split an item-price amount into [goods excl. tax, ['sscl','vat'], total credited].
     *
     * @return array{0: float, 1: array{sscl: float, vat: float}, 2: float}
     */
    public function creditSplit(Order $order, float $itemAmount): array
    {
        $shares = app(SalesAccountingService::class)->returnTaxShares($order, $itemAmount);
        $total = $order->chargesInvoiceTax() ? $order->withInvoiceTax($itemAmount) : round($itemAmount, 2);
        $goods = round($total - $shares['sscl'] - $shares['vat'], 2);

        return [$goods, $shares, $total];
    }

    /** Dr Sales returns (goods) + Dr SSCL payable + Dr VAT output / Cr Accounts receivable (total). */
    public function postCreditNote(CreditNote $cn, ?Order $order = null): void
    {
        if ($cn->type !== CreditNote::TYPE_ADJUSTMENT || $cn->isVoid()) {
            return;
        }
        $order ??= $cn->order;
        $ar = AccountingService::accountId(AccountingService::GL_ACCOUNTS_RECV);
        $returns = AccountingService::accountId(AccountingService::GL_SALES_RETURNS) ?? AccountingService::accountId(AccountingService::GL_SALES_REVENUE);
        $sscl = AccountingService::accountId(AccountingService::GL_SSCL_PAYABLE) ?? AccountingService::accountId(AccountingService::GL_TAX_PAYABLE);
        $vat = AccountingService::accountId(AccountingService::GL_VAT_OUTPUT) ?? AccountingService::accountId(AccountingService::GL_TAX_PAYABLE);
        if (!$ar || !$returns) {
            return;
        }

        $lines = [['account_id' => $returns, 'debit' => $cn->goods_amount, 'credit' => 0, 'memo' => 'Credit note — goods']];
        if ($cn->sscl_amount > 0 && $sscl) {
            $lines[] = ['account_id' => $sscl, 'debit' => $cn->sscl_amount, 'credit' => 0, 'memo' => 'SSCL credited'];
        }
        if ($cn->vat_amount > 0 && $vat) {
            $lines[] = ['account_id' => $vat, 'debit' => $cn->vat_amount, 'credit' => 0, 'memo' => 'Output VAT credited'];
        }
        $debits = round(array_sum(array_column($lines, 'debit')), 2);
        $lines[] = ['account_id' => $ar, 'debit' => 0, 'credit' => $debits, 'memo' => 'Accounts receivable'];

        AccountingService::postMultiLine(
            $lines,
            $cn->credit_note_date->toDateString(),
            'Credit note ' . $cn->credit_note_number . ' — ' . ($order?->invoice_number ?? ''),
            $cn->credit_note_number,
            'credit_note',
            ['source_type' => CreditNote::class, 'source_id' => $cn->id, 'order_id' => $cn->order_id],
            'credit-note:' . $cn->id . ':issued'
        );
    }

    public function voidCreditNote(CreditNote $cn, string $reason): void
    {
        if ($cn->isVoid()) {
            return;
        }
        DB::transaction(function () use ($cn, $reason) {
            $cn->update(['status' => CreditNote::STATUS_VOID, 'voided_at' => now(), 'voided_by' => auth()->id()]);
            AccountingService::reverseEvent('credit-note:' . $cn->id . ':issued');
            AuditLogger::record($cn, 'invoice_voided', [], ['status' => 'void'], $reason);
        });
    }

    /** Document credit note for a confirmed sale return (accounting is posted by the return itself). */
    public function ensureForSaleReturn(SaleReturn $return): ?CreditNote
    {
        if (!\App\Support\SchemaCache::hasTable('credit_notes') || $return->status !== SaleReturn::STATUS_CONFIRMED || (float) $return->total <= 0) {
            return null;
        }
        $existing = CreditNote::query()->where('sale_return_id', $return->id)->first();
        $order = $return->order;
        if (!$order || !$order->isInvoiceIssued()) {
            return $existing;
        }
        [$goods, $shares, $total] = $this->creditSplit($order, (float) $return->total);
        $values = [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => CreditNote::TYPE_RETURN,
            'credit_note_date' => \Illuminate\Support\Carbon::parse($return->return_date ?? now())->toDateString(),
            'reason' => $return->reason ?: 'Goods returned (' . $return->return_no . ')',
            'goods_amount' => $goods,
            'sscl_amount' => $shares['sscl'],
            'vat_amount' => $shares['vat'],
            'total' => $total,
        ];
        if ($existing) {
            $existing->update($values);

            return $existing;
        }

        return CreditNote::query()->create(array_merge($values, [
            'credit_note_number' => CreditNote::nextNumber(),
            'sale_return_id' => $return->id,
            'status' => CreditNote::STATUS_ISSUED,
            'created_by' => $return->created_by ?? auth()->id(),
        ]));
    }

    /** Why an invoice cannot be voided, or null when it can (Xero: void only when nothing is applied to it). */
    public function voidBlocker(Order $order): ?string
    {
        if (!$order->isInvoiceIssued()) {
            return 'Only issued invoices can be voided.';
        }
        $order->loadMissing(['payments', 'saleReturns']);
        if ($order->payments->contains(fn ($p) => !$p->isRefund() && $p->countsTowardPaid())) {
            return 'This invoice has payments. Remove or refund them first, or raise a credit note instead.';
        }
        if ($order->saleReturns->where('status', SaleReturn::STATUS_CONFIRMED)->isNotEmpty()) {
            return 'This invoice has sale returns. Raise a credit note instead.';
        }
        if (\App\Support\SchemaCache::hasTable('credit_notes') && CreditNote::query()->where('order_id', $order->id)->where('status', CreditNote::STATUS_ISSUED)->exists()) {
            return 'This invoice has credit notes. Void them first.';
        }
        if ((float) $order->claim_credit_amount > 0) {
            return 'A return claim was applied to this invoice. Remove it first.';
        }

        return null;
    }

    /** Void: keep the number, reverse the journals, return issued stock. */
    public function voidInvoice(Order $order, string $reason): void
    {
        if ($blocker = $this->voidBlocker($order)) {
            throw new InvalidArgumentException($blocker);
        }

        DB::transaction(function () use ($order, $reason) {
            $before = ['status' => $order->status, 'invoice_total' => $order->invoice_total];

            if (\App\Support\SchemaCache::hasTable('stock_ledgers')) {
                $moves = StockLedger::query()
                    ->where('reference_type', Order::class)
                    ->where('reference_id', $order->id)
                    ->where('transaction_type', 'sale')
                    ->get();
                foreach ($moves as $m) {
                    StockLedger::query()->create([
                        'product_id' => $m->product_id,
                        'product_variation_id' => $m->product_variation_id,
                        'channel' => $m->channel,
                        'transaction_type' => 'sale_void',
                        'quantity' => -1 * (float) $m->quantity,
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'notes' => 'Invoice ' . $order->invoice_number . ' voided — stock returned',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            $order->forceFill([
                'status' => Order::STATUS_CANCELLED,
                'invoice_voided_at' => now(),
                'invoice_voided_by' => auth()->id(),
                'invoice_void_reason' => mb_substr($reason, 0, 255),
            ])->save();

            app(SalesAccountingService::class)->reverseOrder($order->id);
            AuditLogger::record($order, 'invoice_voided', $before, ['status' => 'voided'], $reason);
        });
    }

    /** Overdue invoices with ageing bucket, oldest first. */
    public function overdue(?int $customerId = null): Collection
    {
        $billed = Order::invoiceTotalSql();
        $paidSub = Order::effectivePaidAmountSubquery();

        return Order::query()
            ->with(['customer', 'salesPerson'])
            ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_PAID, Order::STATUS_CREDIT])
            ->whereNotNull('invoice_number')
            ->whereNotNull('invoice_due_date')
            ->whereDate('invoice_due_date', '<', now()->toDateString())
            ->whereRaw("{$billed} > {$paidSub} + 0.009")
            ->when($customerId, fn ($q, $v) => $q->where('customer_id', $v))
            ->orderBy('invoice_due_date')
            ->get()
            ->filter(fn (Order $o) => (float) $o->due_amount > 0.009)
            ->values();
    }

    public function reminderMessage(Order $order): string
    {
        $company = config('dermas.company.name', 'DERMAS');
        $name = $order->customer?->business_name ?: $order->customer?->name ?: 'Customer';

        return "Dear {$name}, invoice {$order->invoice_number} (Rs " . number_format($order->invoice_total, 2) . ') was due on '
            . ($order->invoiceDueDate()?->format('d M Y') ?? '-') . '. Balance Rs ' . number_format((float) $order->due_amount, 2)
            . ". Please arrange payment. Thank you - {$company}";
    }

    /** @return array{sent: bool, message: string} */
    public function sendReminder(Order $order, string $trigger = 'manual'): array
    {
        $phone = trim((string) $order->customer?->phone);
        if ($phone === '') {
            $this->logReminder($order, $trigger, 'skipped', null, null, 'Customer has no phone number');

            return ['sent' => false, 'message' => 'Customer has no phone number.'];
        }
        if ((float) $order->due_amount <= 0.009) {
            return ['sent' => false, 'message' => 'Nothing is due on this invoice.'];
        }
        $message = $this->reminderMessage($order);
        $sent = false;
        try {
            $sent = (bool) app(\App\Services\TextLkSmsService::class)->sendSms($phone, $message);
        } catch (\Throwable) {
            $sent = false;
        }
        if ($sent) {
            $order->forceFill(['last_reminder_at' => now()])->saveQuietly();
            AuditLogger::record($order, 'reminder_sent', [], ['phone' => $phone, 'trigger' => $trigger], $message);
        }
        $this->logReminder($order, $trigger, $sent ? 'sent' : 'failed', $phone, $message, $sent ? null : 'SMS gateway did not accept the message');

        return ['sent' => $sent, 'message' => $sent ? 'Reminder sent to ' . $phone . '.' : 'SMS could not be sent (check the SMS gateway settings).'];
    }

    private function logReminder(Order $order, string $trigger, string $status, ?string $to, ?string $message, ?string $error): void
    {
        if (!\App\Support\SchemaCache::hasTable('acct_reminder_logs')) {
            return;
        }
        DB::table('acct_reminder_logs')->insert([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'channel' => 'sms',
            'recipient' => $to,
            'message' => $message,
            'status' => $status,
            'error' => $error,
            'trigger' => $trigger,
            'days_overdue' => $order->daysOverdue(),
            'sent_by' => $trigger === 'manual' ? auth()->id() : null,
            'created_at' => now(),
        ]);
    }

    /**
     * Automatic reminders: one SMS each time an overdue invoice passes a step in
     * config('dermas.reminder_schedule_days') (e.g. 1, 7, 14, 30 days late), never closer than the minimum gap.
     *
     * @return array{sent: int, failed: int, skipped: int, checked: int}
     */
    public function runReminderSchedule(bool $dryRun = false): array
    {
        $steps = collect(config('dermas.reminder_schedule_days', [1, 7, 14, 30]))->filter(fn ($d) => $d > 0)->sort()->values();
        $gap = max(0, (int) config('dermas.reminder_min_gap_days', 5));
        $stats = ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'checked' => 0];
        if ($steps->isEmpty()) {
            return $stats;
        }

        foreach ($this->overdue() as $order) {
            $stats['checked']++;
            $days = $order->daysOverdue();
            $reached = $steps->filter(fn ($d) => $d <= $days)->count();
            $sentBefore = \App\Support\SchemaCache::hasTable('acct_reminder_logs')
                ? DB::table('acct_reminder_logs')->where('order_id', $order->id)->where('status', 'sent')->count()
                : 0;
            $recent = $order->last_reminder_at && $order->last_reminder_at->gt(now()->subDays($gap));
            if ($reached === 0 || $sentBefore >= $reached || $recent || blank($order->customer?->phone)) {
                continue;
            }
            if ($dryRun) {
                $stats['sent']++;
                continue;
            }
            $res = $this->sendReminder($order, 'schedule');
            $res['sent'] ? $stats['sent']++ : $stats['failed']++;
        }

        return $stats;
    }
}
