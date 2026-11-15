<?php

namespace Modules\Accounting\Http\Controllers;

use App\Services\AccountingService;
use App\Services\ManufacturingOverheadService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ManufacturingAccountingController extends Controller
{
    public function summary(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->endOfMonth()->toDateString());

        $wip = $this->glNetBalance(AccountingService::GL_WORK_IN_PROGRESS);
        $fg = $this->glNetBalance(AccountingService::GL_FINISHED_GOODS);
        $raw = $this->glNetBalance(AccountingService::GL_RAW_MATERIALS);

        $overhead = Schema::hasTable('expenses')
            ? app(ManufacturingOverheadService::class)->simpleAverageForPeriod($from, $to)
            : [
                'total_expenses' => 0,
                'finished_pieces' => 0,
                'overhead_per_piece' => 0,
                'period_from' => $from,
                'period_to' => $to,
                'pieces_source' => 'none',
            ];

        $jobCount = Schema::hasTable('manufacturing_jobs')
            ? (int) DB::table('manufacturing_jobs')->count()
            : 0;

        return view('accounting::phase3.manufacturing.summary', compact('wip', 'fg', 'raw', 'overhead', 'jobCount', 'from', 'to'));
    }

    private function glNetBalance(string $glCode): float
    {
        if (! Schema::hasTable('journal_entries') || ! Schema::hasTable('chart_of_accounts')) {
            return 0.0;
        }

        $accountId = AccountingService::accountId($glCode);
        if (! $accountId) {
            return 0.0;
        }

        $debits = (float) DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->sum('debit');
        $credits = (float) DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->sum('credit');

        return round($debits - $credits, 2);
    }
}