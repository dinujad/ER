<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only Phase 1 diagnostics for journal integrity.
 * Does not modify financial records.
 */
class DiagnoseAccountingIntegrityCommand extends Command
{
    protected $signature = 'accounting:diagnose {--json : Emit JSON}';

    protected $description = 'Read-only scan for unbalanced journals, orphan postings, and duplicate event keys';

    public function handle(): int
    {
        if (! Schema::hasTable('journal_entries')) {
            $this->error('journal_entries table missing.');

            return self::FAILURE;
        }

        $report = [
            'unbalanced_transaction_numbers' => [],
            'orphan_postings' => [],
            'duplicate_active_event_keys' => [],
            'zero_amount_lines' => 0,
            'scanned_at' => now()->toIso8601String(),
        ];

        $unbalanced = DB::table('journal_entries')
            ->select('transaction_number')
            ->selectRaw('ROUND(SUM(COALESCE(debit,0)), 2) as total_debit')
            ->selectRaw('ROUND(SUM(COALESCE(credit,0)), 2) as total_credit')
            ->where(function ($q) {
                $q->where('reversed', 0)->orWhereNull('reversed');
            })
            ->groupBy('transaction_number')
            ->havingRaw('ROUND(SUM(COALESCE(debit,0)), 2) <> ROUND(SUM(COALESCE(credit,0)), 2)')
            ->orderBy('transaction_number')
            ->limit(200)
            ->get();

        $report['unbalanced_transaction_numbers'] = $unbalanced->map(fn ($row) => [
            'transaction_number' => $row->transaction_number,
            'debit' => (float) $row->total_debit,
            'credit' => (float) $row->total_credit,
            'diff' => round((float) $row->total_debit - (float) $row->total_credit, 2),
        ])->all();

        $report['zero_amount_lines'] = (int) DB::table('journal_entries')
            ->where(function ($q) {
                $q->where('reversed', 0)->orWhereNull('reversed');
            })
            ->whereRaw('COALESCE(debit,0) = 0 AND COALESCE(credit,0) = 0')
            ->count();

        if (Schema::hasTable('accounting_postings')) {
            $report['orphan_postings'] = DB::table('accounting_postings as p')
                ->leftJoin('journal_entries as j', 'j.transaction_number', '=', 'p.transaction_number')
                ->where('p.reversed', 0)
                ->where('p.transaction_number', '!=', 'PENDING')
                ->whereNull('j.id')
                ->orderBy('p.id')
                ->limit(100)
                ->get(['p.id', 'p.event_key', 'p.transaction_number', 'p.event_type'])
                ->map(fn ($row) => (array) $row)
                ->all();

            $dupes = DB::table('accounting_postings')
                ->select('event_key')
                ->selectRaw('COUNT(*) as c')
                ->where('reversed', 0)
                ->groupBy('event_key')
                ->havingRaw('COUNT(*) > 1')
                ->limit(100)
                ->get();
            $report['duplicate_active_event_keys'] = $dupes->map(fn ($row) => [
                'event_key' => $row->event_key,
                'count' => (int) $row->c,
            ])->all();
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));
        } else {
            $this->info('Accounting integrity diagnose (read-only)');
            $this->line('Unbalanced txn groups: '.count($report['unbalanced_transaction_numbers']));
            $this->line('Orphan postings: '.count($report['orphan_postings']));
            $this->line('Duplicate active event keys: '.count($report['duplicate_active_event_keys']));
            $this->line('Zero-amount active lines: '.$report['zero_amount_lines']);
            foreach (array_slice($report['unbalanced_transaction_numbers'], 0, 20) as $row) {
                $this->warn("  {$row['transaction_number']} DR {$row['debit']} CR {$row['credit']} diff {$row['diff']}");
            }
        }

        return self::SUCCESS;
    }
}
