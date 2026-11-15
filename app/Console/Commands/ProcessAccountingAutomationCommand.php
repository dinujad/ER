<?php

namespace App\Console\Commands;

use App\Services\Accounting\Ai\WorkflowAutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessAccountingAutomationCommand extends Command
{
    protected $signature = 'accounting:process-automation';

    protected $description = 'Dispatch scheduled accounting automation triggers (idempotent)';

    public function handle(WorkflowAutomationService $workflows): int
    {
        if (! Schema::hasTable('acct_automation_rules')) {
            $this->warn('Automation tables missing');

            return self::SUCCESS;
        }

        $dayKey = now()->toDateString();
        $rules = DB::table('acct_automation_rules')->where('active', true)->whereIn('trigger', [
            'invoice_overdue', 'supplier_bill_due', 'period_ending',
        ])->get();

        foreach ($rules as $rule) {
            if (! in_array($rule->trigger, WorkflowAutomationService::TRIGGERS, true)) {
                continue;
            }
            $workflows->execute((int) $rule->id, 'sched:'.$rule->id.':'.$dayKey, [
                'scheduled' => true,
                'date' => $dayKey,
            ]);
        }

        $this->info('Processed '.$rules->count().' automation rule(s)');

        return self::SUCCESS;
    }
}
