<?php

namespace App\Services\Accounting;

use App\Models\Accounting\BankStatementImport;
use App\Models\Accounting\BankStatementLine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class BankStatementImportService
{
    public const MAX_ROWS = 5000;

    public const MAX_BYTES = 5_242_880; // 5 MB

    /**
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
        return self::importWithErrors($chartOfAccountId, $rows, $filename, $businessId, $userId, $commit)['import'];
    }

    /**
     * @param  array<int, array{date?:string, description?:string, reference?:string, debit?:float|string, credit?:float|string, amount?:float|string}>  $rows
     * @return array{import: BankStatementImport, errors: array<int, string>}
     */
    public static function importWithErrors(
        int $chartOfAccountId,
        array $rows,
        ?string $filename = null,
        int $businessId = 0,
        ?int $userId = null,
        bool $commit = true
    ): array {
        if ($chartOfAccountId <= 0) {
            throw new RuntimeException('Bank GL account is required.');
        }
        if ($rows === []) {
            throw new RuntimeException('No statement rows provided.');
        }
        if (count($rows) > self::MAX_ROWS) {
            throw new RuntimeException('Import exceeds maximum of '.self::MAX_ROWS.' rows.');
        }

        $errors = [];

        $import = DB::transaction(function () use ($chartOfAccountId, $rows, $filename, $businessId, $userId, $commit, &$errors) {
            $import = BankStatementImport::create([
                'business_id' => $businessId,
                'chart_of_account_id' => $chartOfAccountId,
                'original_filename' => $filename,
                'status' => $commit ? 'imported' : 'preview',
                'imported_by' => $userId,
                'row_count' => 0,
            ]);

            $count = 0;
            foreach ($rows as $idx => $row) {
                $rowNum = $idx + 2; // header + 1-based
                $normalized = self::normalizeRow($row, $rowNum, $errors);
                if ($normalized === null) {
                    continue;
                }

                $fingerprint = hash('sha256', implode('|', [
                    (string) ($normalized['date'] ?? ''),
                    (string) ($normalized['description'] ?? ''),
                    (string) ($normalized['reference'] ?? ''),
                    number_format($normalized['debit'], 2, '.', ''),
                    number_format($normalized['credit'], 2, '.', ''),
                ]));

                $exists = BankStatementLine::query()
                    ->whereHas('import', fn ($q) => $q->where('chart_of_account_id', $chartOfAccountId))
                    ->where('fingerprint', $fingerprint)
                    ->exists();

                if ($exists) {
                    $errors[] = "Row {$rowNum}: duplicate statement row skipped.";
                    continue;
                }

                BankStatementLine::create([
                    'bank_statement_import_id' => $import->id,
                    'txn_date' => $normalized['date'],
                    'description' => $normalized['description'],
                    'reference' => $normalized['reference'],
                    'debit' => $normalized['debit'],
                    'credit' => $normalized['credit'],
                    'amount' => $normalized['amount'],
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
                ['row_count' => $count, 'errors' => count($errors)],
                null,
                $businessId,
                $userId
            );

            return $import->load('lines');
        });

        return ['import' => $import, 'errors' => $errors];
    }

    public static function parseUploadedFile(UploadedFile $file, ?array $columnMap = null, int $sheetIndex = 0): array
    {
        if ($file->getSize() && $file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException('File exceeds 5 MB limit.');
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $path = $file->getRealPath();
        if (! $path) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        if (in_array($ext, ['csv', 'txt'], true)) {
            return self::parseCsv((string) file_get_contents($path), $columnMap);
        }

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            return self::parseXlsx($path, $columnMap, $sheetIndex);
        }

        throw new RuntimeException('Unsupported file format. Use CSV or XLSX.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function parseCsv(string $contents, ?array $columnMap = null): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($contents)) ?: [];
        if ($lines === []) {
            return [];
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), str_getcsv(array_shift($lines)));
        $map = self::resolveColumnMap($header, $columnMap);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line);
            $rows[] = self::mapColumns($cols, $map);
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function parseXlsx(string $path, ?array $columnMap = null, int $sheetIndex = 0): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheet($sheetIndex) ?? $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray(null, true, true, false);
        if ($matrix === []) {
            return [];
        }

        $headerRow = array_shift($matrix);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $headerRow ?? []);
        $map = self::resolveColumnMap($header, $columnMap);
        $rows = [];

        foreach ($matrix as $cols) {
            if (! is_array($cols) || self::rowEmpty($cols)) {
                continue;
            }
            // Prevent formula injection: treat values as strings/numbers only (toArray already evaluated display)
            $rows[] = self::mapColumns(array_map(fn ($v) => is_string($v) && str_starts_with(ltrim($v), '=') ? ltrim($v, '=') : $v, $cols), $map);
            if (count($rows) > self::MAX_ROWS) {
                throw new RuntimeException('Import exceeds maximum of '.self::MAX_ROWS.' rows.');
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $cols
     * @param  array{date:int, description:int, reference:?int, debit:?int, credit:?int, amount:?int}  $map
     * @return array<string, mixed>
     */
    protected static function mapColumns(array $cols, array $map): array
    {
        $dateRaw = $cols[$map['date']] ?? null;
        $date = self::parseDateValue($dateRaw);

        return [
            'date' => $date,
            'description' => isset($cols[$map['description']]) ? (string) $cols[$map['description']] : null,
            'reference' => ($map['reference'] !== null && isset($cols[$map['reference']])) ? (string) $cols[$map['reference']] : null,
            'debit' => ($map['debit'] !== null && isset($cols[$map['debit']])) ? $cols[$map['debit']] : null,
            'credit' => ($map['credit'] !== null && isset($cols[$map['credit']])) ? $cols[$map['credit']] : null,
            'amount' => ($map['amount'] !== null && isset($cols[$map['amount']])) ? $cols[$map['amount']] : null,
        ];
    }

    /**
     * @param  array<int, string>  $header
     * @return array{date:int, description:int, reference:?int, debit:?int, credit:?int, amount:?int}
     */
    protected static function resolveColumnMap(array $header, ?array $columnMap): array
    {
        if ($columnMap) {
            return [
                'date' => (int) ($columnMap['date'] ?? 0),
                'description' => (int) ($columnMap['description'] ?? 1),
                'reference' => isset($columnMap['reference']) ? (int) $columnMap['reference'] : null,
                'debit' => isset($columnMap['debit']) ? (int) $columnMap['debit'] : null,
                'credit' => isset($columnMap['credit']) ? (int) $columnMap['credit'] : null,
                'amount' => isset($columnMap['amount']) ? (int) $columnMap['amount'] : null,
            ];
        }

        $find = function (array $aliases) use ($header): ?int {
            foreach ($header as $i => $name) {
                if (in_array($name, $aliases, true)) {
                    return $i;
                }
            }

            return null;
        };

        $date = $find(['date', 'txn_date', 'transaction date', 'value date']);
        $description = $find(['description', 'narration', 'particulars', 'details']);
        if ($date === null || $description === null) {
            throw new RuntimeException('Missing required columns: date and description.');
        }

        return [
            'date' => $date,
            'description' => $description,
            'reference' => $find(['reference', 'ref', 'cheque no', 'cheque_number']),
            'debit' => $find(['debit', 'withdrawal', 'dr']),
            'credit' => $find(['credit', 'deposit', 'cr']),
            'amount' => $find(['amount', 'value', 'txn amount']),
        ];
    }

    protected static function parseDateValue(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        $ts = strtotime((string) $raw);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $errors
     * @return array{date:?string, description:?string, reference:?string, debit:float, credit:float, amount:float}|null
     */
    protected static function normalizeRow(array $row, int $rowNum, array &$errors): ?array
    {
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

        if ($debit < 0 || $credit < 0) {
            $errors[] = "Row {$rowNum}: negative amounts are invalid.";

            return null;
        }
        if ($debit == 0.0 && $credit == 0.0) {
            $errors[] = "Row {$rowNum}: missing amount.";

            return null;
        }
        if (! empty($row['date']) && self::parseDateValue($row['date']) === null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $row['date'])) {
            $errors[] = "Row {$rowNum}: invalid date.";

            return null;
        }

        $date = isset($row['date']) ? (self::parseDateValue($row['date']) ?: (string) $row['date']) : null;

        return [
            'date' => $date,
            'description' => $row['description'] ?? null,
            'reference' => $row['reference'] ?? null,
            'debit' => $debit,
            'credit' => $credit,
            'amount' => $amount != 0.0 ? $amount : round($credit - $debit, 2),
        ];
    }

    /** @param  array<int, mixed>  $cols */
    protected static function rowEmpty(array $cols): bool
    {
        foreach ($cols as $c) {
            if ($c !== null && trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }
}
