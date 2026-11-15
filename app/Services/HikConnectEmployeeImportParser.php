<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HikConnectEmployeeImportParser
{
    public const SOURCE = 'hik-connect';

    /** Columns that must never be imported, logged, or shown. */
    protected const SENSITIVE_HEADERS = [
        'fingerprint',
        'pin',
    ];

    /** Access-control columns that must not grant ERP roles. */
    protected const IGNORED_HEADERS = [
        'card',
        'self-service',
        'self service',
        'extended access',
        'open door via bluetooth',
        'device administrator',
        'super access permission',
    ];

    /**
     * @return array{rows: list<array<string, mixed>>, header_row: int, errors: list<string>}
     */
    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Sheet1') ?? $spreadsheet->getActiveSheet();

        $headerInfo = $this->findHeaderRow($sheet);
        if ($headerInfo === null) {
            return [
                'rows' => [],
                'header_row' => 0,
                'errors' => ['Could not find a Hik-Connect header row (need ID, First Name, Department).'],
            ];
        }

        $headerRow = $headerInfo['row'];
        $columns = $headerInfo['columns'];
        $highestRow = (int) $sheet->getHighestDataRow();
        $rows = [];
        $errors = [];
        $seenIds = [];

        for ($r = $headerRow + 1; $r <= $highestRow; $r++) {
            if ($this->isEmptyRow($sheet, $r, $columns)) {
                continue;
            }

            $parsed = $this->parseDataRow($sheet, $r, $columns);
            if ($parsed['hikvision_person_id'] !== null) {
                $idKey = $parsed['hikvision_person_id'];
                if (isset($seenIds[$idKey])) {
                    $parsed['duplicate_in_file_row'] = $seenIds[$idKey];
                } else {
                    $seenIds[$idKey] = $r;
                }
            }
            $rows[] = $parsed;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [
            'rows' => $rows,
            'header_row' => $headerRow,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{row: int, columns: array<string, int>}|null
     */
    protected function findHeaderRow(Worksheet $sheet): ?array
    {
        $highestRow = min((int) $sheet->getHighestDataRow(), 40);
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($r = 1; $r <= $highestRow; $r++) {
            $columns = [];
            for ($c = 1; $c <= $highestCol; $c++) {
                $label = $this->normalizeHeader($this->cellString($sheet->getCell(Coordinate::stringFromColumnIndex($c) . $r)));
                if ($label === '') {
                    continue;
                }
                if (! isset($columns[$label])) {
                    $columns[$label] = $c;
                }
            }

            $idCol = $this->columnFor($columns, ['id']);
            $firstCol = $this->columnFor($columns, ['first name', 'firstname', 'first_name']);
            $deptCol = $this->columnFor($columns, ['department', 'dept']);

            if ($idCol && $firstCol && $deptCol) {
                return ['row' => $r, 'columns' => $columns];
            }
        }

        return null;
    }

    /**
     * @param  array<string, int>  $columns
     * @return array<string, mixed>
     */
    protected function parseDataRow(Worksheet $sheet, int $excelRow, array $columns): array
    {
        $idCol = $this->columnFor($columns, ['id']);
        $firstCol = $this->columnFor($columns, ['first name', 'firstname', 'first_name']);
        $lastCol = $this->columnFor($columns, ['last name', 'lastname', 'last_name']);
        $deptCol = $this->columnFor($columns, ['department', 'dept']);
        $emailCol = $this->columnFor($columns, ['email']);
        $phoneCol = $this->columnFor($columns, ['phone', 'mobile', 'contact']);
        $remarksCol = $this->columnFor($columns, ['remarks', 'remark', 'notes']);
        $genderCol = $this->columnFor($columns, ['gender']);
        $startCol = $this->columnFor($columns, [
            'start time of effective period',
            'time of effective period (start time)',
            'effective period (start time)',
            'start time',
        ]);
        $endCol = $this->columnFor($columns, [
            'end time of effective period',
            'time of effective period (end time)',
            'effective period (end time)',
            'end time',
        ]);

        $firstName = $firstCol ? $this->cellString($sheet->getCell($this->coord($firstCol, $excelRow))) : '';
        $lastName = $lastCol ? $this->cellString($sheet->getCell($this->coord($lastCol, $excelRow))) : '';
        $displayName = trim(implode(' ', array_filter([$firstName, $lastName], fn ($p) => $p !== '')));

        return [
            'excel_row' => $excelRow,
            'hikvision_person_id' => $idCol
                ? \App\Models\Employee::normalizeHikvisionPersonId($this->cellIdString($sheet->getCell($this->coord($idCol, $excelRow))))
                : null,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'display_name' => $displayName !== '' ? $displayName : null,
            'department' => $deptCol ? ($this->cellString($sheet->getCell($this->coord($deptCol, $excelRow))) ?: null) : null,
            'email' => $emailCol ? ($this->cellString($sheet->getCell($this->coord($emailCol, $excelRow))) ?: null) : null,
            'phone' => $phoneCol ? ($this->cellString($sheet->getCell($this->coord($phoneCol, $excelRow))) ?: null) : null,
            'notes' => $remarksCol ? ($this->cellString($sheet->getCell($this->coord($remarksCol, $excelRow))) ?: null) : null,
            'gender' => $genderCol ? $this->mapGender($this->cellString($sheet->getCell($this->coord($genderCol, $excelRow)))) : null,
            'hikvision_valid_from' => $startCol ? $this->parseHikDateTime($sheet->getCell($this->coord($startCol, $excelRow))) : null,
            'hikvision_valid_to' => $endCol ? $this->parseHikDateTime($sheet->getCell($this->coord($endCol, $excelRow))) : null,
        ];
    }

    /**
     * @param  array<string, int>  $columns
     */
    protected function isEmptyRow(Worksheet $sheet, int $row, array $columns): bool
    {
        foreach ($columns as $label => $col) {
            if (in_array($label, self::SENSITIVE_HEADERS, true)) {
                continue;
            }
            if ($this->cellString($sheet->getCell($this->coord($col, $row))) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, int>  $columns
     * @param  list<string>  $aliases
     */
    protected function columnFor(array $columns, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            if (isset($columns[$alias])) {
                return $columns[$alias];
            }
        }
        foreach ($columns as $label => $col) {
            foreach ($aliases as $alias) {
                if ($label === $alias || str_contains($label, $alias)) {
                    return $col;
                }
            }
        }

        return null;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = preg_replace('/\xEF\xBB\xBF/', '', $header) ?? $header;
        $header = strtolower(trim($header));
        $header = trim($header, "* \t");
        $header = preg_replace('/\s+/', ' ', $header) ?? $header;

        return $header;
    }

    protected function coord(int $col, int $row): string
    {
        return Coordinate::stringFromColumnIndex($col) . $row;
    }

    protected function cellString(Cell $cell): string
    {
        $formatted = trim((string) $cell->getFormattedValue());
        if ($formatted !== '' && $formatted !== '0') {
            return $formatted;
        }
        $raw = $cell->getValue();
        if ($raw === null) {
            return $formatted;
        }

        return trim((string) $raw);
    }

    /** Preserve leading zeros; never coerce the Hik-Connect person ID to int. */
    protected function cellIdString(Cell $cell): string
    {
        $raw = $cell->getValue();
        if (is_string($raw) && trim($raw) !== '') {
            return trim($raw);
        }
        $formatted = trim((string) $cell->getFormattedValue());
        if ($formatted !== '') {
            if (is_numeric($formatted) && str_contains($formatted, '.')) {
                $formatted = rtrim(rtrim($formatted, '0'), '.');
            }

            return $formatted;
        }
        if ($raw === null || $raw === '') {
            return '';
        }
        if (is_numeric($raw) && (float) $raw == (int) $raw) {
            return (string) (int) $raw;
        }

        return trim((string) $raw);
    }

    protected function mapGender(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '' || $value === 'unknown' || $value === 'n/a' || $value === 'na') {
            return null;
        }
        if (in_array($value, ['male', 'm', 'man'], true)) {
            return 'male';
        }
        if (in_array($value, ['female', 'f', 'woman'], true)) {
            return 'female';
        }

        return null;
    }

    protected function parseHikDateTime(Cell $cell): ?string
    {
        $raw = $cell->getValue();
        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            if (is_numeric($raw)) {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);

                return Carbon::instance($dt)->format('Y-m-d H:i:s');
            }
            $text = trim((string) $raw);
            if ($text === '') {
                return null;
            }
            foreach (['Y/m/d H:i:s', 'Y-m-d H:i:s', 'Y/m/d', 'Y-m-d'] as $fmt) {
                try {
                    return Carbon::createFromFormat($fmt, $text)->format('Y-m-d H:i:s');
                } catch (\Throwable) {
                    continue;
                }
            }

            return Carbon::parse($text)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
