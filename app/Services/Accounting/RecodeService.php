<?php

namespace App\Services\Accounting;

use App\Models\AuditLog;
use App\Services\AccountingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6.6 — Find & recode (Xero). Moves selected journal lines to another account by posting a
 * reclassification journal (original lines stay untouched for the audit trail).
 */
class RecodeService
{
    public static function candidates(int $accountId, string $from, string $to, ?string $contains = null, ?string $type = null): Collection
    {
        return DB::table('journal_entries')
            ->where('chart_of_account_id', $accountId)
            ->where('reversed', 0)
            ->whereBetween('date', [$from, $to])
            ->when($contains, fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('reference', 'like', "%{$v}%")))
            ->when($type, fn ($q, $v) => $q->where('transaction_type', $v))
            ->orderBy('date')->orderBy('id')
            ->limit(500)
            ->get()
            ->map(function ($l) {
                $l->source = SourceDocumentResolver::resolve($l->source_type, $l->source_id);
                $l->recoded_to = self::recodedTo((int) $l->id);

                return $l;
            });
    }

    /**
     * @param  array<int, int>  $lineIds
     * @return array{done: int, skipped: array<int, string>}
     */
    public static function recode(array $lineIds, int $targetAccountId, string $reason): array
    {
        $target = DB::table('chart_of_accounts')->where('id', $targetAccountId)->where('active', 1)->first();
        if (!$target) {
            return ['done' => 0, 'skipped' => ['Target account not found.']];
        }

        $done = 0;
        $skipped = [];
        $lines = DB::table('journal_entries')->whereIn('id', array_map('intval', $lineIds))->where('reversed', 0)->get();
        foreach ($lines as $line) {
            $label = $line->transaction_number . ' line ' . $line->id;
            if ((int) $line->chart_of_account_id === $targetAccountId) {
                $skipped[] = "{$label}: already on that account.";
                continue;
            }
            if (self::recodedTo((int) $line->id)) {
                $skipped[] = "{$label}: already recoded.";
                continue;
            }
            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);
            $amount = $debit > 0 ? $debit : $credit;
            if ($amount <= 0) {
                continue;
            }
            $from = (int) $line->chart_of_account_id;
            $entries = $debit > 0
                ? [['account_id' => $targetAccountId, 'debit' => $amount, 'credit' => 0, 'memo' => 'Recoded to'], ['account_id' => $from, 'debit' => 0, 'credit' => $amount, 'memo' => 'Recoded from']]
                : [['account_id' => $from, 'debit' => $amount, 'credit' => 0, 'memo' => 'Recoded from'], ['account_id' => $targetAccountId, 'debit' => 0, 'credit' => $amount, 'memo' => 'Recoded to']];

            try {
                $txn = AccountingService::postMultiLine(
                    $entries,
                    (string) $line->date,
                    'Recode: ' . mb_substr((string) $line->name, 0, 150),
                    $line->transaction_number,
                    'recode',
                    ['source_type' => 'journal_recode', 'source_id' => (int) $line->id],
                    'recode:' . $line->id
                );
            } catch (\Throwable $e) {
                $skipped[] = "{$label}: " . $e->getMessage();
                continue;
            }

            self::audit($line, $target, $txn, $reason);
            $done++;
        }

        return ['done' => $done, 'skipped' => $skipped];
    }

    /** Transaction number of the live recode journal for a line, if any. */
    public static function recodedTo(int $lineId): ?string
    {
        if (!Schema::hasTable('accounting_postings')) {
            return null;
        }
        $row = DB::table('accounting_postings')->where('event_key', 'recode:' . $lineId)->where('reversed', false)->first();

        return $row && $row->transaction_number !== 'PENDING' ? $row->transaction_number : null;
    }

    private static function audit(object $line, object $target, string $txn, string $reason): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }
        $user = auth()->user();
        $old = DB::table('chart_of_accounts')->where('id', $line->chart_of_account_id)->first();
        AuditLog::query()->create([
            'user_id' => $user?->getAuthIdentifier(),
            'user_name' => $user->name ?? 'System',
            'event' => 'recoded',
            'auditable_type' => 'journal_entry',
            'auditable_id' => $line->id,
            'document_label' => 'Journal ' . $line->transaction_number,
            'old_values' => ['account' => trim(($old->gl_code ?? '') . ' ' . ($old->name ?? '')), 'amount' => (float) ($line->debit ?: $line->credit)],
            'new_values' => ['account' => trim($target->gl_code . ' ' . $target->name), 'recode_journal' => $txn],
            'note' => $reason,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
