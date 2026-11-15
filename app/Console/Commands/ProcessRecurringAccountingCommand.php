<?php

namespace App\Console\Commands;

use App\Services\Accounting\RecurringAccountingService;
use Illuminate\Console\Command;

class ProcessRecurringAccountingCommand extends Command
{
    protected $signature = 'accounting:process-recurring {--date=}';

    protected $description = 'Process due recurring accounting journal templates (idempotent).';

    public function handle(): int
    {
        $date = $this->option('date') ?: null;
        $count = RecurringAccountingService::processDue($date);
        $this->info("Processed {$count} due recurring template(s).");

        return self::SUCCESS;
    }
}
