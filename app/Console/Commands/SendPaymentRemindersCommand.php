<?php

namespace App\Console\Commands;

use App\Services\Accounting\ReceivablesService;
use Illuminate\Console\Command;

/**
 * Phase 7 — automatic SMS reminders for overdue invoices (scheduled daily).
 * Off until DERMAS_AUTO_REMINDERS=true, so it never texts customers by surprise.
 */
class SendPaymentRemindersCommand extends Command
{
    protected $signature = 'accounting:send-payment-reminders {--force : run even when automatic reminders are switched off} {--dry-run : only count who would get a reminder}';

    protected $description = 'Send scheduled SMS payment reminders for overdue invoices';

    public function handle(ReceivablesService $receivables): int
    {
        if (!config('dermas.auto_reminders') && !$this->option('force') && !$this->option('dry-run')) {
            $this->info('Automatic reminders are off (DERMAS_AUTO_REMINDERS=false).');

            return self::SUCCESS;
        }
        $stats = $receivables->runReminderSchedule((bool) $this->option('dry-run'));
        $this->info(sprintf('Checked %d overdue invoice(s): %s %d, failed %d.', $stats['checked'], $this->option('dry-run') ? 'would send' : 'sent', $stats['sent'], $stats['failed']));

        return self::SUCCESS;
    }
}
