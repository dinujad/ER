<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeImportController extends Controller
{
    public function create()
    {
        $roles = Role::orderBy('label')->get();
        return view('erp.employees.import', compact('roles'));
    }

    protected function getRowsFromFile(string $path, string $extension): array
    {
        $ext = strtolower($extension);
        if (in_array($ext, ['xlsx', 'xls'])) {
            if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                try {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                    $sheet = $spreadsheet->getActiveSheet();
                    $arr = $sheet->toArray(null, true, true, true);
                    return is_array($arr) ? $arr : [];
                } catch (\Throwable $e) {
                    return [];
                }
            }
            return []; // Excel but no reader: show "save as CSV" message
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }
        $content = preg_replace('/\xEF\xBB\xBF/', '', $content); // UTF-8 BOM
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = str_getcsv($line);
        }
        return $rows;
    }

    protected function normalizeHeader(string $h): string
    {
        $h = preg_replace('/\xEF\xBB\xBF/', '', $h);
        $h = trim($h, " \t\n\r\0\x0B*");
        return strtolower($h);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'default_role' => 'nullable|string|max:50',
        ]);
        $defaultRole = $request->input('default_role') ?: 'employee';
        if (! Role::where('name', $defaultRole)->exists()) {
            $defaultRole = 'employee';
        }

        $file = $request->file('file');
        $path = $file->getRealPath();
        $ext = $file->getClientOriginalExtension() ?: pathinfo($path, PATHINFO_EXTENSION);

        $rows = $this->getRowsFromFile($path, $ext);

        if (empty($rows)) {
            if (in_array(strtolower($ext), ['xlsx', 'xls'])) {
                return back()->with('error', 'Excel file could not be read. Please save the file as CSV: Open in Excel → Save As → CSV UTF-8 (Comma delimited), then upload the CSV.');
            }
            return back()->with('error', 'File is empty or invalid.');
        }

        $rawHeader = $rows[0];
        if (is_array($rawHeader) && isset($rawHeader['A'])) {
            $rawHeader = array_values($rawHeader);
        }
        $header = array_map(function ($h) {
            $h = is_numeric($h) ? (string) $h : (string) $h;
            return $this->normalizeHeader($h);
        }, $rawHeader);
        $headerOriginal = array_map(function ($h) {
            $h = is_numeric($h) ? (string) $h : (string) $h;
            return trim(preg_replace('/\xEF\xBB\xBF/', '', $h), " \t*");
        }, $rawHeader);
        $dataRows = array_slice($rows, 1);

        $idx = function ($keys) use ($header, $headerOriginal) {
            foreach ((array) $keys as $k) {
                $k = strtolower($k);
                foreach ($header as $i => $h) {
                    $h = (string) $h;
                    $orig = (string) ($headerOriginal[$i] ?? '');
                    if ($h === $k || str_contains($h, $k) || str_contains($orig, $k) || str_contains($k, $h)) {
                        return $i;
                    }
                }
            }
            return null;
        };

        $idCol = $idx(['id', 'employee id', 'employee_id', 'emp id', 'emp_id']);
        // Don't use "First Name" / "Last Name" column as ID (avoid wrong match)
        if ($idCol !== null) {
            $idHeader = strtolower((string) ($header[$idCol] ?? '') . ($headerOriginal[$idCol] ?? ''));
            if (str_contains($idHeader, 'first') || str_contains($idHeader, 'last') || $idHeader === 'name') {
                $idCol = null;
            }
        }
        $firstCol = $idx(['first name', 'first_name', 'firstname']);
        if ($firstCol === null) {
            $firstCol = $idx(['name']);
        }
        $lastCol = $idx(['last name', 'last_name', 'lastname']);
        $deptCol = $idx(['department', 'dept']);
        $startCol = $idx(['start', 'effective period (start', 'effective period start', 'start time']);
        $endCol = $idx(['end', 'effective period (end', 'effective period end', 'end time']);
        $selfServiceCol = $idx(['self-service', 'self_service', 'self service']);
        $emailCol = $idx(['email']);

        if ($firstCol === null && $idCol === null) {
            $preview = implode(', ', array_slice(array_map(function ($h) {
                return is_string($h) ? $h : json_encode($h);
            }, $rawHeader), 0, 8));
            return back()->with('error', 'Could not find required columns (ID, First Name, or Name). Your first row has: ' . $preview . '. Use CSV with headers like: ID, First Name, Last Name, Department, Email.');
        }

        $imported = 0;
        $errors = [];
        $numCols = count($header);

        foreach ($dataRows as $rowNum => $row) {
            $rowNum += 2; // 1-based + header
            if (is_array($row) && isset($row['A'])) {
                $row = array_values($row);
            }
            $row = array_pad((array) $row, $numCols, '');
            $get = function ($col) use ($row) {
                $val = $row[$col] ?? '';
                return is_scalar($val) ? trim((string) $val) : '';
            };
            $externalId = $idCol !== null ? $get($idCol) : '';
            $firstName = $firstCol !== null ? $get($firstCol) : '';
            $lastName = $lastCol !== null ? $get($lastCol) : '';
            // CSV has only First Name: use it for both first_name and last_name so last_name is not required; display name stays single.
            if ($lastName === '' && $firstName !== '') {
                $lastName = $firstName;
            }
            $deptName = $deptCol !== null ? $get($deptCol) : 'Main';
            $startStr = $startCol !== null ? $get($startCol) : '';
            $endStr = $endCol !== null ? $get($endCol) : '';
            $selfServiceStr = $selfServiceCol !== null ? strtolower($get($selfServiceCol)) : 'disable';
            $email = $emailCol !== null ? $get($emailCol) : '';

            // Single display name: first name only when CSV had only first name; otherwise first + last
            $name = trim($firstName . ' ' . $lastName) ?: ('Employee ' . ($externalId ?: $rowNum));
            if ($firstName !== '' && $lastName === $firstName) {
                $name = $firstName;
            }
            if (empty($name)) {
                $errors[] = "Row {$rowNum}: Skip (no name).";
                continue;
            }

            $nic = 'IMP-' . ($externalId ?: Str::random(6)) . '-' . $rowNum;
            if (Employee::where('nic', $nic)->exists()) {
                $nic = 'IMP-' . Str::random(8) . '-' . $rowNum;
            }

            $department = null;
            if ($deptName) {
                $department = Department::firstOrCreate(
                    ['name' => $deptName],
                    ['code' => Str::slug($deptName)]
                );
            }

            $effectiveStart = null;
            $effectiveEnd = null;
            if ($startStr) {
                try {
                    $effectiveStart = \Carbon\Carbon::parse($startStr)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $effectiveStart = null;
                }
            }
            if ($endStr) {
                try {
                    $effectiveEnd = \Carbon\Carbon::parse($endStr)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $effectiveEnd = null;
                }
            }

            $selfService = in_array($selfServiceStr, ['enable', '1', 'yes', 'true'], true);

            $payload = [
                'name' => $name,
                'first_name' => $firstName ?: null,
                'last_name' => $lastName ?: null,
                'nic' => $nic,
                'email' => $email ?: null,
                'contact' => 'N/A',
                'role' => $defaultRole,
                'join_date' => $effectiveStart ?? now()->toDateString(),
                'department_id' => $department?->id,
                'effective_period_start' => $effectiveStart,
                'effective_period_end' => $effectiveEnd,
                'self_service' => $selfService,
                'status' => 'active',
            ];
            // Only save as Employee ID if value looks like an ID (number or short code), not a name
            $validId = $externalId !== '' && (is_numeric($externalId) || (strlen($externalId) <= 15 && preg_match('/^[A-Za-z0-9_-]+$/', $externalId)));
            if ($validId) {
                $payload['device_enroll_id'] = (string) $externalId;
            }

            try {
                if ($validId && $existing = Employee::where('device_enroll_id', (string) $externalId)->first()) {
                    unset($payload['nic']);
                    $existing->update($payload);
                } else {
                    Employee::create($payload);
                }
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }

        $msg = "Imported {$imported} employee(s).";
        if (!empty($errors)) {
            $msg .= ' ' . count($errors) . ' row(s) had errors: ' . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $msg .= '...';
            }
        }

        return redirect()->route('employees.index')->with(
            $imported > 0 ? 'success' : 'error',
            $msg
        );
    }
}
