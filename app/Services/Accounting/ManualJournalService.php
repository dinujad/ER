<?php

namespace App\Services\Accounting;

use App\Models\Accounting\ManualJournal;
use App\Models\Accounting\ManualJournalLine;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ManualJournalService
{
    public static function nextJournalNumber(int $businessId = 0): string
    {
        $year = date('Y');
        $prefix = 'MJ-'.$year.'-';
        $last = ManualJournal::query()
            ->where('business_id', $businessId)
            ->where('journal_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('journal_number');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, array{account_id:int, debit?:float|string, credit?:float|string, memo?:string}>  $lines
     */
    public static function createDraft(array $header, array $lines, ?int $userId = null): ManualJournal
    {
        $lines = self::filterEmptyLines($lines);
        self::assertLinesValid($lines);

        return DB::transaction(function () use ($header, $lines, $userId) {
            $journal = ManualJournal::create([
                'business_id' => (int) ($header['business_id'] ?? 0),
                'journal_number' => $header['journal_number'] ?? self::nextJournalNumber((int) ($header['business_id'] ?? 0)),
                'journal_date' => $header['journal_date'],
                'status' => 'draft',
                'description' => $header['description'] ?? null,
                'reference' => $header['reference'] ?? null,
                'location_id' => $header['location_id'] ?? null,
                'created_by' => $userId,
            ]);

            self::replaceLines($journal, $lines);

            AccountingAuditService::record(
                'manual_journal.created',
                $journal,
                null,
                $journal->load('lines')->toArray(),
                null,
                (int) $journal->business_id,
                $userId
            );

            return $journal->fresh('lines');
        });
    }

    /**
     * @param  array<int, array{account_id:int, debit?:float|string, credit?:float|string, memo?:string}>  $lines
     */
    public static function updateDraft(ManualJournal $journal, array $header, array $lines, ?int $userId = null): ManualJournal
    {
        if ($journal->status !== 'draft' && $journal->status !== 'rejected') {
            throw new RuntimeException('Only draft or rejected journals can be edited.');
        }

        $lines = self::filterEmptyLines($lines);
        self::assertLinesValid($lines);

        return DB::transaction(function () use ($journal, $header, $lines, $userId) {
            $before = $journal->load('lines')->toArray();
            $journal->fill([
                'journal_date' => $header['journal_date'] ?? $journal->journal_date,
                'description' => $header['description'] ?? $journal->description,
                'reference' => $header['reference'] ?? $journal->reference,
                'location_id' => $header['location_id'] ?? $journal->location_id,
                'status' => 'draft',
                'rejection_reason' => null,
                'rejected_at' => null,
            ])->save();

            self::replaceLines($journal, $lines);

            AccountingAuditService::record(
                'manual_journal.updated',
                $journal,
                $before,
                $journal->fresh('lines')->toArray(),
                null,
                (int) $journal->business_id,
                $userId
            );

            return $journal->fresh('lines');
        });
    }

    public static function submit(ManualJournal $journal, ?int $userId = null): ManualJournal
    {
        $journal->load('lines');
        if (! $journal->isBalanced()) {
            throw new RuntimeException('Journal must be balanced before submit.');
        }
        if (! in_array($journal->status, ['draft', 'rejected'], true)) {
            throw new RuntimeException('Only draft/rejected journals can be submitted.');
        }

        $journal->status = 'submitted';
        $journal->submitted_by = $userId;
        $journal->submitted_at = now();
        $journal->save();

        AccountingAuditService::record('manual_journal.submitted', $journal, null, ['status' => 'submitted'], null, (int) $journal->business_id, $userId);

        return $journal;
    }

    public static function approve(ManualJournal $journal, ?int $userId = null): ManualJournal
    {
        if ($journal->status !== 'submitted') {
            throw new RuntimeException('Only submitted journals can be approved.');
        }

        $journal->status = 'approved';
        $journal->approved_by = $userId;
        $journal->approved_at = now();
        $journal->save();

        AccountingAuditService::record('manual_journal.approved', $journal, null, ['status' => 'approved'], null, (int) $journal->business_id, $userId);

        return $journal;
    }

    public static function reject(ManualJournal $journal, string $reason, ?int $userId = null): ManualJournal
    {
        if ($journal->status !== 'submitted') {
            throw new RuntimeException('Only submitted journals can be rejected.');
        }

        $journal->status = 'rejected';
        $journal->rejection_reason = $reason;
        $journal->rejected_at = now();
        $journal->save();

        AccountingAuditService::record('manual_journal.rejected', $journal, null, ['status' => 'rejected', 'reason' => $reason], $reason, (int) $journal->business_id, $userId);

        return $journal;
    }

    /**
     * Post approved (or draft when approval not required) multi-line journal via AccountingService.
     */
    public static function post(ManualJournal $journal, bool $requireApproval = true, ?int $userId = null): ManualJournal
    {
        $journal->load('lines');

        if ($requireApproval && $journal->status !== 'approved') {
            throw new RuntimeException('Journal must be approved before posting.');
        }

        if (! $requireApproval && ! in_array($journal->status, ['draft', 'approved', 'submitted'], true)) {
            throw new RuntimeException('Journal cannot be posted from status '.$journal->status);
        }

        if (! $journal->isBalanced()) {
            throw new RuntimeException('Cannot post unbalanced journal.');
        }

        if ($journal->transaction_number) {
            throw new RuntimeException('Journal already posted.');
        }

        $date = $journal->journal_date->format('Y-m-d');
        AccountingPeriodService::assertOpenForDate($date, (int) $journal->business_id);

        $lines = [];
        foreach ($journal->lines as $line) {
            $lines[] = [
                'account_id' => (int) $line->chart_of_account_id,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'memo' => $line->memo,
            ];
        }

        $txn = AccountingService::postMultiLine(
            $lines,
            $date,
            $journal->description ?: $journal->journal_number,
            $journal->reference,
            'manual_entry',
            [
                'manual_entry' => 1,
                'created_by_id' => $userId ?? $journal->created_by,
                'location_id' => $journal->location_id,
            ],
            'manual_journal:'.$journal->id,
            (int) $journal->business_id
        );

        $journal->status = 'posted';
        $journal->transaction_number = $txn;
        $journal->posting_date = $date;
        $journal->posted_by = $userId;
        $journal->posted_at = now();
        $journal->save();

        AccountingAuditService::record('manual_journal.posted', $journal, null, ['transaction_number' => $txn], null, (int) $journal->business_id, $userId);

        return $journal;
    }

    public static function reverse(ManualJournal $journal, ?int $userId = null): ManualJournal
    {
        if ($journal->status !== 'posted' || ! $journal->transaction_number) {
            throw new RuntimeException('Only posted journals can be reversed.');
        }
        if ($journal->reversed_by_txn) {
            throw new RuntimeException('Journal already reversed.');
        }

        $revTxn = AccountingService::reverseTransaction($journal->transaction_number, null, $userId);

        $journal->status = 'reversed';
        $journal->reversed_by_txn = $revTxn;
        $journal->save();

        AccountingAuditService::record('manual_journal.reversed', $journal, null, ['reversed_by_txn' => $revTxn], null, (int) $journal->business_id, $userId);

        return $journal;
    }

    /**
     * @param  array<int, array{account_id?:int, debit?:float|string, credit?:float|string, memo?:string}>  $lines
     * @return array<int, array{account_id:int, debit?:float|string, credit?:float|string, memo?:string}>
     */
    public static function filterEmptyLines(array $lines): array
    {
        return array_values(array_filter($lines, function ($line) {
            $accountId = (int) ($line['account_id'] ?? 0);
            $debit = isset($line['debit']) && is_numeric($line['debit']) ? (float) $line['debit'] : 0.0;
            $credit = isset($line['credit']) && is_numeric($line['credit']) ? (float) $line['credit'] : 0.0;

            return $accountId > 0 && ($debit > 0 || $credit > 0);
        }));
    }

    /**
     * @param  array<int, array{account_id:int, debit?:float|string, credit?:float|string, memo?:string}>  $lines
     */
    public static function assertLinesValid(array $lines): void
    {
        if (count($lines) < 2) {
            throw new RuntimeException('At least two journal lines are required.');
        }

        $debit = '0.00';
        $credit = '0.00';

        foreach ($lines as $i => $line) {
            $d = isset($line['debit']) && is_numeric($line['debit']) ? round((float) $line['debit'], 2) : 0.0;
            $c = isset($line['credit']) && is_numeric($line['credit']) ? round((float) $line['credit'], 2) : 0.0;

            if ($d < 0 || $c < 0) {
                throw new RuntimeException('Line '.($i + 1).': amounts cannot be negative.');
            }
            if ($d > 0 && $c > 0) {
                throw new RuntimeException('Line '.($i + 1).': cannot have both debit and credit.');
            }
            if ($d == 0.0 && $c == 0.0) {
                throw new RuntimeException('Line '.($i + 1).': must have debit or credit.');
            }
            if (empty($line['account_id'])) {
                throw new RuntimeException('Line '.($i + 1).': account is required.');
            }

            $debit = number_format((float) $debit + $d, 2, '.', '');
            $credit = number_format((float) $credit + $c, 2, '.', '');
        }

        if ($debit !== $credit) {
            throw new RuntimeException("Journal unbalanced: debit {$debit} != credit {$credit}.");
        }
        if ((float) $debit <= 0) {
            throw new RuntimeException('Journal total must be greater than zero.');
        }
    }

    /**
     * @param  array<int, array{account_id:int, debit?:float|string, credit?:float|string, memo?:string}>  $lines
     */
    protected static function replaceLines(ManualJournal $journal, array $lines): void
    {
        ManualJournalLine::where('manual_journal_id', $journal->id)->delete();

        $n = 1;
        foreach ($lines as $line) {
            ManualJournalLine::create([
                'manual_journal_id' => $journal->id,
                'chart_of_account_id' => (int) $line['account_id'],
                'debit' => isset($line['debit']) ? round((float) $line['debit'], 4) : 0,
                'credit' => isset($line['credit']) ? round((float) $line['credit'], 4) : 0,
                'memo' => $line['memo'] ?? null,
                'line_no' => $n++,
            ]);
        }
    }
}
