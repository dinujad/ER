<?php

namespace App\Services\Accounting;

use App\Models\Accounting\RecurringAccountingRun;
use App\Models\Accounting\RecurringAccountingTemplate;
use App\Models\Order;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class RecurringAccountingService
{
    public static function createTemplate(array $data, ?int $userId = null): RecurringAccountingTemplate
    {
        $docType = $data['document_type'] ?? 'journal';
        $payload = $data['payload'] ?? [];

        if ($docType === 'journal') {
            if (empty($payload['lines']) || ! is_array($payload['lines'])) {
                throw new RuntimeException('Recurring journal requires lines in payload.');
            }
            ManualJournalService::assertLinesValid($payload['lines']);
        } elseif ($docType === 'invoice') {
            if (empty($payload['customer_id']) || empty($payload['amount'])) {
                throw new RuntimeException('Recurring invoice requires customer_id and amount.');
            }
        } elseif ($docType === 'bill') {
            if (empty($payload['supplier_id']) || empty($payload['amount'])) {
                throw new RuntimeException('Recurring bill requires supplier_id and amount.');
            }
        } else {
            throw new RuntimeException('Unsupported document_type.');
        }

        $starts = Carbon::parse($data['starts_on'])->toDateString();

        return RecurringAccountingTemplate::create([
            'business_id' => (int) ($data['business_id'] ?? 0),
            'name' => $data['name'],
            'document_type' => $docType,
            'frequency' => $data['frequency'],
            'starts_on' => $starts,
            'ends_on' => $data['ends_on'] ?? null,
            'max_occurrences' => $data['max_occurrences'] ?? null,
            'occurrence_count' => 0,
            'next_run_on' => $data['next_run_on'] ?? $starts,
            'status' => $data['status'] ?? 'active',
            'execution_mode' => $data['execution_mode'] ?? 'draft',
            'payload' => $payload,
            'created_by' => $userId,
        ]);
    }

    public static function executeOccurrence(RecurringAccountingTemplate $template, string $occurrenceDate, ?int $userId = null): RecurringAccountingRun
    {
        if ($template->status !== 'active') {
            throw new RuntimeException('Template is not active.');
        }
        if ($template->ends_on && $occurrenceDate > $template->ends_on->format('Y-m-d')) {
            throw new RuntimeException('Template has ended.');
        }
        if ($template->max_occurrences && (int) $template->occurrence_count >= (int) $template->max_occurrences) {
            throw new RuntimeException('Maximum occurrences reached.');
        }

        $executionKey = 'recurring:'.$template->id.':'.$occurrenceDate;

        return DB::transaction(function () use ($template, $occurrenceDate, $userId, $executionKey) {
            $existing = RecurringAccountingRun::query()
                ->where(function ($q) use ($template, $occurrenceDate, $executionKey) {
                    $q->where(function ($w) use ($template, $occurrenceDate) {
                        $w->where('template_id', $template->id)->whereDate('occurrence_date', $occurrenceDate);
                    })->orWhere('execution_key', $executionKey);
                })
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            try {
                $docType = $template->document_type ?: 'journal';
                $runData = [
                    'template_id' => $template->id,
                    'occurrence_date' => $occurrenceDate,
                    'status' => 'created',
                    'execution_key' => $executionKey,
                    'message' => 'OK',
                ];

                if ($docType === 'journal') {
                    $journal = ManualJournalService::createDraft([
                        'business_id' => $template->business_id,
                        'journal_date' => $occurrenceDate,
                        'description' => ($template->payload['description'] ?? $template->name).' (recurring)',
                        'reference' => $template->payload['reference'] ?? ('REC-'.$template->id),
                        'location_id' => $template->payload['location_id'] ?? null,
                    ], $template->payload['lines'] ?? [], $userId);

                    if ($template->execution_mode === 'auto_post') {
                        ManualJournalService::post($journal, false, $userId);
                    }
                    $runData['manual_journal_id'] = $journal->id;
                    $runData['generated_type'] = 'manual_journal';
                    $runData['generated_id'] = $journal->id;
                } elseif ($docType === 'invoice') {
                    $order = self::createDraftInvoice($template, $occurrenceDate, $userId);
                    $runData['generated_type'] = 'order';
                    $runData['generated_id'] = $order->id;
                    $runData['message'] = 'Draft invoice #'.$order->id.' (no stock/revenue until finalized)';
                } else {
                    $bill = self::createDraftBill($template, $occurrenceDate, $userId);
                    $runData['generated_type'] = 'purchase';
                    $runData['generated_id'] = $bill->id;
                    $runData['message'] = 'Draft bill #'.$bill->id.' (no inventory/AP until confirmed)';
                }

                $run = RecurringAccountingRun::create($runData);

                $template->occurrence_count = (int) $template->occurrence_count + 1;
                $template->next_run_on = self::nextDate($occurrenceDate, $template->frequency);
                if ($template->ends_on && $template->next_run_on && $template->next_run_on->format('Y-m-d') > $template->ends_on->format('Y-m-d')) {
                    $template->status = 'ended';
                    $template->next_run_on = null;
                }
                if ($template->max_occurrences && (int) $template->occurrence_count >= (int) $template->max_occurrences) {
                    $template->status = 'ended';
                    $template->next_run_on = null;
                }
                $template->save();

                AccountingAuditService::record(
                    'recurring.executed',
                    $run,
                    null,
                    $runData,
                    null,
                    (int) $template->business_id,
                    $userId
                );

                return $run;
            } catch (\Throwable $e) {
                return RecurringAccountingRun::create([
                    'template_id' => $template->id,
                    'occurrence_date' => $occurrenceDate,
                    'status' => 'failed',
                    'execution_key' => $executionKey,
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Retry a failed occurrence (Phase 7). A failed run used to block its template for ever
     * (the next run found the failed row and stopped); this clears it and runs the occurrence again.
     */
    public static function retryFailedRun(int $runId, ?int $userId = null): RecurringAccountingRun
    {
        $run = RecurringAccountingRun::query()->findOrFail($runId);
        if ($run->status !== 'failed') {
            throw new RuntimeException('Only failed runs can be retried.');
        }
        $template = RecurringAccountingTemplate::query()->findOrFail($run->template_id);
        $date = $run->occurrence_date->format('Y-m-d');
        $run->delete();

        return self::executeOccurrence($template, $date, $userId);
    }

    public static function processDue(?string $asOf = null): int
    {
        $asOf = $asOf ?: date('Y-m-d');
        $count = 0;
        $templates = RecurringAccountingTemplate::query()
            ->where('status', 'active')
            ->whereNotNull('next_run_on')
            ->whereDate('next_run_on', '<=', $asOf)
            ->get();

        foreach ($templates as $template) {
            self::executeOccurrence($template, $template->next_run_on->format('Y-m-d'));
            $count++;
        }

        return $count;
    }

    public static function nextDate(string $from, string $frequency): Carbon
    {
        $d = Carbon::parse($from);

        // Carbon addMonth/addYear handles month-end and leap years.
        return match ($frequency) {
            'daily' => $d->copy()->addDay(),
            'weekly' => $d->copy()->addWeek(),
            'monthly' => $d->copy()->addMonthNoOverflow(),
            'quarterly' => $d->copy()->addMonthsNoOverflow(3),
            'yearly' => $d->copy()->addYearNoOverflow(),
            default => $d->copy()->addMonthNoOverflow(),
        };
    }

    protected static function createDraftInvoice(RecurringAccountingTemplate $template, string $date, ?int $userId): Order
    {
        if (! Schema::hasTable('orders')) {
            throw new RuntimeException('Orders table missing.');
        }

        $amount = round((float) ($template->payload['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new RuntimeException('Invoice amount must be positive.');
        }

        $data = [
            'customer_id' => (int) $template->payload['customer_id'],
            'total_amount' => $amount,
            'status' => Order::STATUS_DRAFT,
            'notes' => 'Recurring template #'.$template->id.' · '.$template->name.' · '.$date,
        ];

        if (Schema::hasColumn('orders', 'order_date')) {
            $data['order_date'] = $date;
        }
        // Phase 7 — orders.order_number and user_id are required in DERMAS; without them every recurring invoice failed.
        if (Schema::hasColumn('orders', 'order_number')) {
            $data['order_number'] = Order::nextOrderNumber();
        }
        if (Schema::hasColumn('orders', 'user_id')) {
            $data['user_id'] = (int) ($template->payload['sales_person_id'] ?? $userId ?? $template->created_by ?? 0) ?: null;
        }
        if (Schema::hasColumn('orders', 'subtotal')) {
            $data['subtotal'] = $amount;
        }
        if (Schema::hasColumn('orders', 'created_by')) {
            $data['created_by'] = $userId;
        }
        if (Schema::hasColumn('orders', 'invoice_no') && ! empty($template->payload['invoice_prefix'])) {
            $data['invoice_no'] = $template->payload['invoice_prefix'].'-'.$date;
        }

        return Order::create($data);
    }

    protected static function createDraftBill(RecurringAccountingTemplate $template, string $date, ?int $userId): Purchase
    {
        if (! Schema::hasTable('purchases')) {
            throw new RuntimeException('Purchases table missing.');
        }

        $amount = round((float) ($template->payload['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new RuntimeException('Bill amount must be positive.');
        }

        $data = [
            'supplier_id' => (int) $template->payload['supplier_id'],
            'date' => $date,
            'purchase_type' => Purchase::TYPE_CREDIT,
            'status' => Purchase::STATUS_DRAFT,
            'subtotal' => $amount,
            'total' => $amount,
            'paid_amount' => 0,
            'notes' => 'Recurring template #'.$template->id.' · '.$template->name,
            'reference_no' => 'REC-BILL-'.$template->id.'-'.$date,
            'created_by' => $userId,
        ];

        if (Schema::hasColumn('purchases', 'purchase_no')) {
            $data['purchase_no'] = 'REC-'.$template->id.'-'.str_replace('-', '', $date);
        }

        return Purchase::create($data);
    }
}
