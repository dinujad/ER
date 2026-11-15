<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HikConnectEmployeeImportService
{
    public const STATUS_NEW = 'new';
    public const STATUS_EXISTING = 'existing';
    public const STATUS_CONFLICT = 'conflict';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_LINK = 'link';

    public function __construct(
        protected HikConnectEmployeeImportParser $parser
    ) {}

    /**
     * @return array{rows: list<array<string, mixed>>, header_row: int, errors: list<string>}
     */
    public function preview(string $path, array $links = []): array
    {
        $parsed = $this->parser->parse($path);
        $classified = [];
        foreach ($parsed['rows'] as $row) {
            $classified[] = $this->classify($row, $links);
        }

        return [
            'rows' => $classified,
            'header_row' => $parsed['header_row'],
            'errors' => $parsed['errors'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<int|string, int|string>  $links  excel_row => employee_id
     * @return array{created: int, updated: int, skipped: int, failed: int, errors: list<string>}
     */
    public function confirm(array $rows, array $links = []): array
    {
        $created = $updated = $skipped = $failed = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $links, &$created, &$updated, &$skipped, &$failed, &$errors) {
            foreach ($rows as $row) {
                $classified = $this->classify($row, $links);
                $excelRow = (int) ($classified['excel_row'] ?? 0);

                if ($classified['status'] === self::STATUS_INVALID || $classified['status'] === self::STATUS_CONFLICT) {
                    $failed++;
                    $errors[] = 'Row ' . $excelRow . ': ' . ($classified['message'] ?? 'Cannot import.');
                    continue;
                }

                try {
                    if ($classified['status'] === self::STATUS_NEW) {
                        $this->createEmployee($classified);
                        $created++;
                    } elseif (in_array($classified['status'], [self::STATUS_EXISTING, self::STATUS_LINK], true)) {
                        $employee = Employee::query()->find($classified['employee_id'] ?? 0);
                        if (! $employee) {
                            $failed++;
                            $errors[] = 'Row ' . $excelRow . ': Linked employee no longer exists.';
                            continue;
                        }
                        $this->updateEmployee($employee, $classified);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = 'Row ' . $excelRow . ': ' . $e->getMessage();
                }
            }
        });

        return compact('created', 'updated', 'skipped', 'failed', 'errors');
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int|string, int|string>  $links
     * @return array<string, mixed>
     */
    public function classify(array $row, array $links = []): array
    {
        $excelRow = (int) ($row['excel_row'] ?? 0);
        $personId = Employee::normalizeHikvisionPersonId($row['hikvision_person_id'] ?? null);
        $displayName = trim((string) ($row['display_name'] ?? ''));
        $first = trim((string) ($row['first_name'] ?? ''));
        $last = trim((string) ($row['last_name'] ?? ''));

        $row['hikvision_person_id'] = $personId;
        $row['status'] = self::STATUS_NEW;
        $row['employee_id'] = null;
        $row['employee_name'] = null;
        $row['message'] = null;

        if ($personId === null) {
            $row['status'] = self::STATUS_INVALID;
            $row['message'] = 'Missing Hik-Connect ID.';

            return $row;
        }

        if ($displayName === '' && $first === '' && $last === '') {
            $row['status'] = self::STATUS_INVALID;
            $row['message'] = 'At least one of first name or last name is required.';

            return $row;
        }

        if (! empty($row['duplicate_in_file_row'])) {
            $row['status'] = self::STATUS_CONFLICT;
            $row['message'] = 'Duplicate Hik-Connect ID in this file (also row ' . $row['duplicate_in_file_row'] . ').';

            return $row;
        }

        $mapped = Employee::query()
            ->where('hikvision_source', Employee::HIK_SOURCE_HIK_CONNECT)
            ->where('hikvision_person_id', $personId)
            ->first();

        $byDevice = Employee::query()->where('device_enroll_id', $personId)->first();
        $linkId = isset($links[$excelRow]) && $links[$excelRow] !== '' && $links[$excelRow] !== null
            ? (int) $links[$excelRow]
            : 0;

        if ($linkId > 0) {
            $target = Employee::query()->find($linkId);
            if (! $target) {
                $row['status'] = self::STATUS_INVALID;
                $row['message'] = 'Linked employee was not found.';

                return $row;
            }
            if ($mapped && (int) $mapped->id !== $linkId) {
                $row['status'] = self::STATUS_CONFLICT;
                $row['employee_id'] = $mapped->id;
                $row['employee_name'] = $mapped->display_name;
                $row['message'] = 'Hik-Connect ID ' . $personId . ' is already mapped to employee #' . $mapped->id . ' (' . $mapped->display_name . '). Mapping was not overwritten.';

                return $row;
            }
            if ($target->hikvision_person_id
                && $target->hikvision_person_id !== $personId
                && $target->hikvision_source === Employee::HIK_SOURCE_HIK_CONNECT) {
                $row['status'] = self::STATUS_CONFLICT;
                $row['employee_id'] = $target->id;
                $row['employee_name'] = $target->display_name;
                $row['message'] = 'Employee #' . $target->id . ' is already mapped to Hik-Connect ID ' . $target->hikvision_person_id . '. Mapping was not overwritten.';

                return $row;
            }
            $row['status'] = self::STATUS_LINK;
            $row['employee_id'] = $target->id;
            $row['employee_name'] = $target->display_name;
            $row['message'] = 'Will link to existing employee #' . $target->id . '.';

            return $row;
        }

        if ($mapped) {
            $row['status'] = self::STATUS_EXISTING;
            $row['employee_id'] = $mapped->id;
            $row['employee_name'] = $mapped->display_name;
            $row['message'] = 'Already mapped. Non-blank fields will update.';

            return $row;
        }

        if ($byDevice && (! $byDevice->hikvision_person_id || $byDevice->hikvision_person_id === $personId)) {
            $row['status'] = self::STATUS_EXISTING;
            $row['employee_id'] = $byDevice->id;
            $row['employee_name'] = $byDevice->display_name;
            $row['message'] = 'Matched existing EMP enroll ID. Will store Hik-Connect mapping.';

            return $row;
        }

        if ($byDevice && $byDevice->hikvision_person_id && $byDevice->hikvision_person_id !== $personId) {
            $row['status'] = self::STATUS_CONFLICT;
            $row['employee_id'] = $byDevice->id;
            $row['employee_name'] = $byDevice->display_name;
            $row['message'] = 'Device enroll ID ' . $personId . ' belongs to employee #' . $byDevice->id . ' who is mapped to a different Hik-Connect ID.';

            return $row;
        }

        $row['status'] = self::STATUS_NEW;
        $row['message'] = 'New employee. Optionally link to an existing ERP employee instead.';

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function createEmployee(array $row): Employee
    {
        $department = Department::resolveFromImportPath($row['department'] ?? null);
        $personId = $row['hikvision_person_id'];
        $name = $row['display_name'] ?: trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));

        $employee = Employee::create([
            'name' => $name,
            'first_name' => $row['first_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'email' => $row['email'] ?? null,
            'nic' => $this->uniqueImportNic(),
            'contact' => $row['phone'] ?? '',
            'role' => 'employee',
            'join_date' => now()->toDateString(),
            'department_id' => $department?->id,
            'hikvision_person_id' => $personId,
            'hikvision_source' => Employee::HIK_SOURCE_HIK_CONNECT,
            'device_enroll_id' => $this->availableDeviceEnrollId($personId, null),
            'gender' => $row['gender'] ?? null,
            'notes' => $row['notes'] ?? null,
            'hikvision_valid_from' => $row['hikvision_valid_from'] ?? null,
            'hikvision_valid_to' => $row['hikvision_valid_to'] ?? null,
            'status' => 'active',
            'self_service' => false,
        ]);

        return $employee;
    }

    /**
     * Blank imported cells must not erase existing details.
     *
     * @param  array<string, mixed>  $row
     */
    protected function updateEmployee(Employee $employee, array $row): Employee
    {
        $payload = [
            'hikvision_person_id' => $row['hikvision_person_id'],
            'hikvision_source' => Employee::HIK_SOURCE_HIK_CONNECT,
        ];

        if (! empty($row['first_name'])) {
            $payload['first_name'] = $row['first_name'];
        }
        if (! empty($row['last_name'])) {
            $payload['last_name'] = $row['last_name'];
        }
        if (! empty($row['display_name'])) {
            $payload['name'] = $row['display_name'];
        } elseif (! empty($payload['first_name']) || ! empty($payload['last_name'])) {
            $payload['name'] = trim(($payload['first_name'] ?? $employee->first_name) . ' ' . ($payload['last_name'] ?? $employee->last_name));
        }
        if (! empty($row['email'])) {
            $payload['email'] = $row['email'];
        }
        if (! empty($row['phone'])) {
            $payload['contact'] = $row['phone'];
        }
        if (! empty($row['notes'])) {
            $payload['notes'] = $row['notes'];
        }
        if (array_key_exists('gender', $row) && $row['gender'] !== null) {
            $payload['gender'] = $row['gender'];
        }
        if (! empty($row['hikvision_valid_from'])) {
            $payload['hikvision_valid_from'] = $row['hikvision_valid_from'];
        }
        if (! empty($row['hikvision_valid_to'])) {
            $payload['hikvision_valid_to'] = $row['hikvision_valid_to'];
        }
        if (! empty($row['department'])) {
            $department = Department::resolveFromImportPath($row['department']);
            if ($department) {
                $payload['department_id'] = $department->id;
            }
        }

        $enroll = $this->availableDeviceEnrollId($row['hikvision_person_id'], $employee->id);
        if ($enroll !== null && empty($employee->device_enroll_id)) {
            $payload['device_enroll_id'] = $enroll;
        }

        $employee->update($payload);

        return $employee->fresh();
    }

    protected function uniqueImportNic(): string
    {
        do {
            $nic = 'IMP-' . Str::lower((string) Str::ulid());
        } while (Employee::query()->where('nic', $nic)->exists());

        return $nic;
    }

    protected function availableDeviceEnrollId(string $personId, ?int $exceptEmployeeId): ?string
    {
        $taken = Employee::query()
            ->where('device_enroll_id', $personId)
            ->when($exceptEmployeeId, fn ($q) => $q->where('id', '!=', $exceptEmployeeId))
            ->exists();

        return $taken ? null : $personId;
    }
}
