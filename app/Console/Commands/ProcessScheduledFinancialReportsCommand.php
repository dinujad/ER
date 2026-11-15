<?php

namespace App\Console\Commands;

use App\Services\Accounting\ScheduledFinancialReportService;
use Illuminate\Console\Command;

class ProcessScheduledFinancialReportsCommand extends Command
{
    protected $signature = 'accounting:process-scheduled-reports {--date=}';

    protected $description = 'Process due scheduled financial report jobs (idempotent event_key).';

    public function handle(): int
    {
        $date = $this->option('date') ?: null;
        $count = ScheduledFinancialReportService::processDue($date);
        $this->info("Processed {$count} scheduled report(s).");

        return self::SUCCESS;
    }
}