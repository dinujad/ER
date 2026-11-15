<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Payment;
use App\Services\ChequePaymentNotifier;
use Illuminate\Console\Command;

class SendChequeDateRemindersCommand extends Command
{
    protected $signature = 'cheque:send-due-reminders';

    protected $description = 'Send cheque-date reminders: admin+sales WA on cheque day; customer reminder X days before';

    public function handle(ChequePaymentNotifier $notifier): int
    {
        $today = now()->toDateString();

        // --- 1. On cheque date: alert admin + sales person ---
        $dueCount = 0;
        Payment::query()
            ->whereRaw('LOWER(payment_method) = ?', ['cheque'])
            ->whereDate('cheque_date', $today)
            ->whereNull('cheque_followup_sent_at')
            ->with(['order.customer', 'order.salesPerson.employee'])
            ->chunkById(50, function ($payments) use ($notifier, &$dueCount) {
                foreach ($payments as $payment) {
                    if (!$payment->order) {
                        continue;
                    }
                    try {
                        $notifier->notifyChequeDueToday($payment);
                        $payment->forceFill(['cheque_followup_sent_at' => now()])->save();
                        $dueCount++;
                    } catch (\Throwable $e) {
                        $this->error('Due-today payment '.$payment->id.': '.$e->getMessage());
                    }
                }
            });

        $this->info("Due-today reminders sent: {$dueCount}");

        // --- 2. X days before cheque date: remind customer ---
        $daysBefore = (int) AppSetting::get(AppSetting::KEY_CUSTOMER_REMIND_DAYS_BEFORE, '3');
        if ($daysBefore > 0) {
            $targetDate = now()->addDays($daysBefore)->toDateString();

            $custCount = 0;
            Payment::query()
                ->whereRaw('LOWER(payment_method) = ?', ['cheque'])
                ->whereDate('cheque_date', $targetDate)
                ->whereNull('customer_reminder_sent_at')
                ->with(['order.customer', 'order.salesPerson.employee'])
                ->chunkById(50, function ($payments) use ($notifier, &$custCount) {
                    foreach ($payments as $payment) {
                        if (!$payment->order || !$payment->order->customer) {
                            continue;
                        }
                        try {
                            $notifier->notifyCustomerReminder($payment);
                            $payment->forceFill(['customer_reminder_sent_at' => now()])->save();
                            $custCount++;
                        } catch (\Throwable $e) {
                            $this->error('Customer-reminder payment '.$payment->id.': '.$e->getMessage());
                        }
                    }
                });

            $this->info("Customer reminders sent ({$daysBefore} days before cheque date): {$custCount}");
        }

        return self::SUCCESS;
    }
}
