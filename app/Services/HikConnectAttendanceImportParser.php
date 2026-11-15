<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HikConnectAttendanceImportParser
{
    /**
     * @return array{rows: list<array<string, mixed>>, header_row: int, sheet: string, errors: list<string>}
     */
    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $located = $this->locateHeader($spreadsheet);
        if ($located === null) {
            $spreadsheet->disconnectWorksheets();

            return [
                'rows' => [],
                'header_row' => 0,
                'sheet' => '',
                'errors' => ['Could not find a Hik-Connect attendance header row (need ID, Date, Clock-In Time, Clock-Out Time).'],
            ];
        }

        /** @var Worksheet $sheet */
        $sheet = $located['sheet'];
        $headerRow = $located['row'];
        $columns = $located['columns'];
        $highest = (int) $sheet->getHighestDataRow();
        $rows = [];

        for ($r = $headerRow + 1; $r <= $highest; $r++) {
            if ($this->isEmptyRow($sheet, $r, $columns)) {
                continue;
            }
            $rows[] = $this->parseDataRow($sheet, $r, $columns);
        }

        $title = $sheet->getTitle();
        $spreadsheet->disconnectWorksheets();

        return [
            'rows' => $rows,
            'header_row' => $headerRow,
            'sheet' => $title,
            'errors' => [],
        ];
    }

    /**
     * Duration `09:10` is 9 hours 10 minutes (550). `--` and blanks stay null. `00:00` is 0.
     *
     * @return array{minutes: ?int, invalid: bool}
     */
    public function parseDuration(mixed $value): array
    {
        if ($value === null) {
            return ['minutes' => null, 'invalid' => false];
        }
        if (is_numeric($value) && ! is_string($value)) {
            $minutes = (int) round(((float) $value) * 24 * 60);

            return ['minutes' => max(0, $minutes), 'invalid' => false];
        }

        $text = trim((string) $value);
        if ($text === '' || $text === '--' || $text === '-' || strcasecmp($text, 'n/a') === 0) {
            return ['minutes' => null, 'invalid' => false];
        }
        if (preg_match('/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/', $text, $m)) {
            $hours = (int) $m[1];
            $mins = (int) $m[2];
            $secs = isset($m[3]) ? (int) $m[3] : 0;
            if ($mins > 59 || $secs > 59) {
                return ['minutes' => null, 'invalid' => true];
            }

            return ['minutes' => ($hours * 60) + $mins + (int) round($secs / 60), 'invalid' => false];
        }

        return ['minutes' => null, 'invalid' => true];
    }

    /**
     * @return array{sheet: Worksheet, row: int, columns: array<string, int>}|null
     */
    protected function locateHeader(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): ?array
    {
        $preferred = $spreadsheet->getSheetByName('Sheet0');
        $sheets = $preferred ? [$preferred] : [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if ($preferred && $sheet->getTitle() === $preferred->getTitle()) {
                continue;
            }
            $sheets[] = $sheet;
        }

        foreach ($sheets as $sheet) {
            $found = $this->findHeaderRow($sheet);
            if ($found !== null) {
                return ['sheet' => $sheet, 'row' => $found['row'], 'columns' => $found['columns']];
            }
        }

        return null;
    }

    /**
     * @return array{row: int, columns: array<string, int>}|null
     */
    protected function findHeaderRow(Worksheet $sheet): ?array
    {
        $highestRow = min((int) $sheet->getHighestDataRow(), 30);
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn() ?: 'A');

        for ($r = 1; $r <= $highestRow; $r++) {
            $columns = [];
            for ($c = 1; $c <= $highestCol; $c++) {
                $label = $this->normalizeHeader($this->cellString($sheet->getCell($this->coord($c, $r))));
                if ($label !== '' && ! isset($columns[$label])) {
                    $columns[$label] = $c;
                }
            }
            if (isset($columns['id'], $columns['date'], $columns['clock in time'], $columns['clock out time'])) {
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
        $invalid = [];
        $duration = function (string $header) use ($sheet, $excelRow, $columns, &$invalid) {
            $col = $columns[$header] ?? null;
            if (! $col) {
                return null;
            }
            $parsed = $this->parseDuration($this->rawOrFormatted($sheet->getCell($this->coord($col, $excelRow)), true));
            if ($parsed['invalid']) {
                $invalid[] = $header;
            }

            return $parsed['minutes'];
        };

        $hikId = isset($columns['id'])
            ? \App\Models\Employee::normalizeHikvisionPersonId($this->cellIdString($sheet->getCell($this->coord($columns['id'], $excelRow))))
            : null;

        $date = isset($columns['date']) ? $this->parseDate($sheet->getCell($this->coord($columns['date'], $excelRow))) : null;
        if (isset($columns['date']) && $this->present($sheet->getCell($this->coord($columns['date'], $excelRow))) && $date === null) {
            $invalid[] = 'date';
        }

        return [
            'excel_row' => $excelRow,
            'hikvision_person_id' => $hikId,
            'first_name' => $this->text($sheet, $columns, $excelRow, 'first name'),
            'last_name' => $this->text($sheet, $columns, $excelRow, 'last name'),
            'department' => $this->text($sheet, $columns, $excelRow, 'department'),
            'date' => $date,
            'weekday' => $this->text($sheet, $columns, $excelRow, 'weekday'),
            'timetable' => $this->text($sheet, $columns, $excelRow, 'timetable'),
            'scheduled_start' => $this->combine(
                $this->parseDate($this->cell($sheet, $columns, $excelRow, 'work start date')),
                $this->parseTime($this->cell($sheet, $columns, $excelRow, 'work start time'))
            ),
            'scheduled_end' => $this->combine(
                $this->parseDate($this->cell($sheet, $columns, $excelRow, 'work end date')),
                $this->parseTime($this->cell($sheet, $columns, $excelRow, 'work end time'))
            ),
            'clock_in' => $this->combine(
                $this->parseDate($this->cell($sheet, $columns, $excelRow, 'clock in date')),
                $this->parseTime($this->cell($sheet, $columns, $excelRow, 'clock in time'))
            ),
            'clock_out' => $this->combine(
                $this->parseDate($this->cell($sheet, $columns, $excelRow, 'clock out date')),
                $this->parseTime($this->cell($sheet, $columns, $excelRow, 'clock out time'))
            ),
            'clock_in_source' => $this->text($sheet, $columns, $excelRow, 'clock in source'),
            'clock_out_source' => $this->text($sheet, $columns, $excelRow, 'clock out source'),
            'hikvision_status' => $this->text($sheet, $columns, $excelRow, 'attendance status'),
            'worked_minutes' => $duration('worked hours'),
            'absent_minutes' => $duration('absent duration'),
            'late_minutes' => $duration('late duration'),
            'early_leave_minutes' => $duration('early leave duration'),
            'break_minutes' => $duration('break duration'),
            'leave_minutes' => $duration('leave duration'),
            'overtime_minutes' => $duration('overtime duration'),
            'workday_overtime_minutes' => $duration('workday overtime duration'),
            'weekend_overtime_minutes' => $duration('weekend overtime duration'),
            'invalid_fields' => $invalid,
        ];
    }

    /**
     * @param  array<string, int>  $columns
     */
    protected function isEmptyRow(Worksheet $sheet, int $row, array $columns): bool
    {
        foreach ($columns as $col) {
            if ($this->cellString($sheet->getCell($this->coord($col, $row))) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, int>  $columns
     */
    protected function text(Worksheet $sheet, array $columns, int $row, string $header): ?string
    {
        if (! isset($columns[$header])) {
            return null;
        }
        $value = $this->cellString($sheet->getCell($this->coord($columns[$header], $row)));
        if ($value === '' || $value === '--' || $value === '-') {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, int>  $columns
     */
    protected function cell(Worksheet $sheet, array $columns, int $row, string $header): ?Cell
    {
        if (! isset($columns[$header])) {
            return null;
        }

        return $sheet->getCell($this->coord($columns[$header], $row));
    }

    protected function present(?Cell $cell): bool
    {
        if (! $cell) {
            return false;
        }
        $text = $this->cellString($cell);

        return $text !== '' && $text !== '--' && $text !== '-';
    }

    protected function parseDate(?Cell $cell): ?string
    {
        if (! $cell || ! $this->present($cell)) {
            return null;
        }
        $raw = $cell->getValue();
        $tz = config('app.timezone', 'Asia/Colombo');
        try {
            if (is_numeric($raw)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw))->timezone($tz)->format('Y-m-d');
            }
            $text = trim((string) ($cell->getFormattedValue() ?: $raw));
            if ($text === '' || $text === '--') {
                return null;
            }
            foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'm/d/Y'] as $fmt) {
                try {
                    return Carbon::createFromFormat($fmt, $text, $tz)->format('Y-m-d');
                } catch (\Throwable) {
                }
            }

            return Carbon::parse($text, $tz)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseTime(?Cell $cell): ?string
    {
        if (! $cell || ! $this->present($cell)) {
            return null;
        }
        $raw = $cell->getValue();
        try {
            if (is_numeric($raw) && ! is_string($raw)) {
                $minutes = (int) round(((float) $raw) * 24 * 60);
                $minutes = $minutes % (24 * 60);

                return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            }
            $text = trim((string) ($cell->getFormattedValue() ?: $raw));
            if ($text === '' || $text === '--' || $text === '-') {
                return null;
            }
            if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $text, $m)) {
                return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
            }

            return Carbon::parse($text, config('app.timezone', 'Asia/Colombo'))->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function combine(?string $date, ?string $time): ?string
    {
        if ($date === null || $time === null) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time, config('app.timezone', 'Asia/Colombo'))
            ->format('Y-m-d H:i:s');
    }

    protected function rawOrFormatted(Cell $cell, bool $duration): mixed
    {
        $formatted = trim((string) $cell->getFormattedValue());
        if ($formatted !== '' && preg_match('/^\d+:\d{2}/', $formatted)) {
            return $formatted;
        }
        if ($formatted === '--' || $formatted === '-' || $formatted === '') {
            $raw = $cell->getValue();
            if ($raw === null || $raw === '' || $formatted === '--' || $formatted === '-') {
                return $formatted === '' ? $raw : $formatted;
            }
        }
        $raw = $cell->getValue();
        if ($duration && is_numeric($raw) && ($formatted === '' || ! preg_match('/^\d+:\d{2}/', $formatted))) {
            return $raw;
        }

        return $formatted !== '' ? $formatted : $raw;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace(['-', '_'], ' ', $header);
        $header = preg_replace('/\s+/', ' ', $header) ?? $header;

        return trim($header, "* ");
    }

    protected function cellString(Cell $cell): string
    {
        $formatted = trim((string) $cell->getFormattedValue());
        if ($formatted !== '') {
            return $formatted;
        }
        $raw = $cell->getValue();

        return $raw === null ? '' : trim((string) $raw);
    }

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

    protected function coord(int $col, int $row): string
    {
        return Coordinate::stringFromColumnIndex($col) . $row;
    }
}
