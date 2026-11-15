<?php

namespace App\Services\Accounting;

use App\Models\Accounting\BankStatementImport;
use App\Models\Accounting\BankStatementLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BankStatementImportService
{
    /**
     * Preview/import CSV rows. Does NOT create GL journals automatically.
     *
     * @param  array<int, array{date?:string, description?:string, reference?:string, debit?:float|string, credit?:float|string, amount?:float|string}>  $rows
     */
    public static function import(
        int $chartOfAccountId,
        array $rows,
        ?string $filename = null,
        int $businessId = 0,
        ?int $userId = null,
        bool $commit = true
    ): BankStatementImport {
        if ($chartOfAccountId <= 0) {
            throw new RuntimeException('Bank GL account is required.');
        }
        if ($rows === []) {
            throw new RuntimeException('No statement rows provided.');
        }

        return DB::transaction(function () use ($chartOfAccountId, $rows, $filename, $businessId, $userId, $commit) {
            $import = BankStatementImport::create([
                'business_id' => $businessId,
                'chart_of_account_id' => $chartOfAccountId,
                'original_filename' => $filename,
                'status' => $commit ? 'imported' : 'preview',
                'imported_by' => $userId,
                'row_count' => 0,
            ]);

            $count = 0;
            foreach ($rows as $row) {
                $debit = isset($row['debit']) && is_numeric($row['debit']) ? round((float) $row['debit'], 2) : 0.0;
                $credit = isset($row['credit']) && is_numeric($row['credit']) ? round((float) $row['credit'], 2) : 0.0;
                $amount = isset($row['amount']) && is_numeric($row['amount']) ? round((float) $row['amount'], 2) : 0.0;

                if ($amount != 0.0 && $debit == 0.0 && $credit == 0.0) {
                    if ($amount < 0) {
                        $debit = abs($amount);
                    } else {
                        $credit = $amount;
                    }
                }

                $fingerprint = hash('sha256', implode('|', [
                    (string) ($row['date'] ?? ''),
                    (string) ($row['description'] ?? ''),
                    (string) ($row['reference'] ?? ''),
                    number_format($debit, 2, '.', ''),
                    number_format($credit, 2, '.', ''),
                ]));

                $exists = BankStatementLine::query()
                    ->whereHas('import', fn ($q) => $q->where('chart_of_account_id', $chartOfAccountId))
                    ->where('fingerprint', $fingerprint)
                    ->exists();

                if ($exists) {
                    continue;
                }

                BankStatementLine::create([
                    'bank_statement_import_id' => $import->id,
                    'txn_date' => $row['date'] ?? null,
                    'description' => $row['description'] ?? null,
                    'reference' => $row['reference'] ?? null,
                    'debit' => $debit,
                    'credit' => $credit,
                    'amount' => $amount != 0.0 ? $amount : round($credit - $debit, 2),
                    'fingerprint' => $fingerprint,
                    'match_status' => 'unmatched',
                ]);
                $count++;
            }

            $import->row_count = $count;
            $import->save();

            AccountingAuditService::record(
                'bank_statement.imported',
                $import,
                null,
                ['row_count' => $count],
                null,
                $businessId,
                $userId
            );

            return $import->load('lines');
        });
    }

    /**
     * Parse simple CSV: date,description,reference,debit,credit OR date,description,reference,amount
     *
     * @return array<int, array<string, mixed>>
     */
    public static function parseCsv(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($contents)) ?: [];
        if ($lines === []) {
            return [];
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line);
            $map = [];
            foreach ($header as $i => $key) {
                $map[$key] = $cols[$i] ?? null;
            }

            $rows[] = [
                'date' => $map['date'] ?? $map['txn_date'] ?? null,
                'description' => $map['description'] ?? $map['narration'] ?? null,
                'reference' => $map['reference'] ?? $map['ref'] ?? null,
                'debit' => $map['debit'] ?? null,
                'credit' => $map['credit'] ?? null,
                'amount' => $map['amount'] ?? null,
            ];
        }

        return $rows;
    }
}
