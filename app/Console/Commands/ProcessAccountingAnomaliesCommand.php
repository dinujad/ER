<?php

namespace App\Console\Commands;

use App\Services\Accounting\Ai\AnomalyDetectionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessAccountingAnomaliesCommand extends Command
{
    protected $signature = 'accounting:process-anomalies {--business=0}';

    protected $description = 'Run deterministic financial anomaly detection (review alerts only)';

    public function handle(AnomalyDetectionService $service): int
    {
        $businessId = (int) $this->option('business');
        $ids = $businessId > 0
            ? [$businessId]
            : (Schema::hasTable('acct_anomaly_rules')
                ? DB::table('acct_anomaly_rules')->distinct()->pluck('business_id')->all()
                : [0]);

        foreach ($ids as $bid) {
            $alerts = $service->run((int) $bid);
            $this->info('Business '.$bid.': '.count($alerts).' alert(s)');
        }

        return self::SUCCESS;
    }
}
