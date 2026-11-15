<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\HikConnectEmployeeImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HikConnectEmployeeImportController extends Controller
{
    public const SESSION_KEY = 'hik_connect_employee_import';

    public function __construct(
        protected HikConnectEmployeeImportService $import
    ) {}

    public function create()
    {
        return view('erp.employees.hik-connect-import');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', $this->excelFileRule()],
        ]);

        $file = $request->file('file');
        $stored = $file->store('hik-connect-imports');
        $fullPath = Storage::path($stored);

        try {
            $preview = $this->import->preview($fullPath);
        } catch (\Throwable $e) {
            Storage::delete($stored);

            return back()->with('error', 'Could not read this workbook. Use the Hik-Connect Person Information .xlsx export.');
        }

        if ($preview['rows'] === [] && $preview['errors'] !== []) {
            Storage::delete($stored);

            return back()->with('error', implode(' ', $preview['errors']));
        }

        session([
            self::SESSION_KEY => [
                'file' => $stored,
                'original_name' => $file->getClientOriginalName(),
                'rows' => array_map(fn ($row) => $this->sessionRow($row), $preview['rows']),
                'header_row' => $preview['header_row'],
            ],
        ]);

        $linkable = $this->linkableEmployees();

        return view('erp.employees.hik-connect-import-preview', [
            'rows' => $preview['rows'],
            'headerRow' => $preview['header_row'],
            'originalName' => $file->getClientOriginalName(),
            'linkable' => $linkable,
            'counts' => $this->statusCounts($preview['rows']),
        ]);
    }

    public function store(Request $request)
    {
        $payload = session(self::SESSION_KEY);
        if (! is_array($payload) || empty($payload['rows'])) {
            return redirect()
                ->route('employees.hik-connect-import.create')
                ->with('error', 'Upload the Hik-Connect workbook again. The preview expired.');
        }

        $links = [];
        foreach ((array) $request->input('links', []) as $excelRow => $employeeId) {
            if ($employeeId !== null && $employeeId !== '') {
                $links[(int) $excelRow] = (int) $employeeId;
            }
        }

        $result = $this->import->confirm($payload['rows'], $links);

        if (! empty($payload['file'])) {
            Storage::delete($payload['file']);
        }
        session()->forget(self::SESSION_KEY);

        $msg = sprintf(
            'Hik-Connect import finished. Created %d, updated %d, skipped %d, failed %d.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
            $result['failed']
        );

        if ($result['errors'] !== []) {
            $msg .= ' ' . implode(' ', array_slice($result['errors'], 0, 8));
            if (count($result['errors']) > 8) {
                $msg .= ' …';
            }
        }

        return redirect()
            ->route('employees.index')
            ->with($result['failed'] > 0 && $result['created'] + $result['updated'] === 0 ? 'error' : 'success', $msg)
            ->with('hik_connect_import_result', $result);
    }

    protected function excelFileRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            $ext = strtolower((string) $value->getClientOriginalExtension());
            if (! in_array($ext, ['xlsx', 'xls'], true)) {
                $fail('Upload an Excel workbook (.xlsx or .xls).');
            }
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function sessionRow(array $row): array
    {
        return [
            'excel_row' => $row['excel_row'] ?? null,
            'hikvision_person_id' => $row['hikvision_person_id'] ?? null,
            'first_name' => $row['first_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'display_name' => $row['display_name'] ?? null,
            'department' => $row['department'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'notes' => $row['notes'] ?? null,
            'gender' => $row['gender'] ?? null,
            'hikvision_valid_from' => $row['hikvision_valid_from'] ?? null,
            'hikvision_valid_to' => $row['hikvision_valid_to'] ?? null,
            'duplicate_in_file_row' => $row['duplicate_in_file_row'] ?? null,
        ];
    }

    protected function linkableEmployees()
    {
        return Employee::query()
            ->unmappedHikvision()
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'nic', 'device_enroll_id']);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    protected function statusCounts(array $rows): array
    {
        $counts = ['new' => 0, 'existing' => 0, 'conflict' => 0, 'invalid' => 0, 'link' => 0];
        foreach ($rows as $row) {
            $status = $row['status'] ?? 'invalid';
            if (! isset($counts[$status])) {
                $counts[$status] = 0;
            }
            $counts[$status]++;
        }

        return $counts;
    }
}
