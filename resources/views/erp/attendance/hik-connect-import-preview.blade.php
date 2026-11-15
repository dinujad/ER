@extends('erp.layout')

@section('title', 'Preview Hik-Connect Attendance')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Preview Hik-Connect Attendance</h1>
            <p class="text-muted small mb-0">
                {{ $originalName }} · {{ $sheet }} · header row {{ $headerRow }} ·
                New {{ $counts['new'] ?? 0 }} ·
                Update {{ $counts['update'] ?? 0 }} ·
                Unchanged {{ $counts['unchanged'] ?? 0 }} ·
                Unmatched {{ $counts['unmatched'] ?? 0 }} ·
                Conflict {{ $counts['conflict'] ?? 0 }} ·
                Invalid {{ $counts['invalid'] ?? 0 }}
            </p>
        </div>
        <a href="{{ route('attendance.hik-connect-import.create') }}" class="btn btn-outline-secondary btn-sm">Upload a different file</a>
    </div>

    <form action="{{ route('attendance.hik-connect-import.store') }}" method="POST">
        @csrf
        <div class="card shadow-sm border-0 mb-3">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Excel</th>
                            <th>Employee</th>
                            <th>Hikvision ID</th>
                            <th>Date</th>
                            <th>In</th>
                            <th>Out</th>
                            <th>Source status</th>
                            <th class="text-end">Worked</th>
                            <th class="text-end">Late</th>
                            <th class="text-end">OT</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $status = $row['status'] ?? 'invalid';
                                $badge = [
                                    'new' => 'bg-success',
                                    'update' => 'bg-primary',
                                    'unchanged' => 'bg-secondary',
                                    'unmatched' => 'bg-warning text-dark',
                                    'conflict' => 'bg-danger',
                                    'invalid' => 'bg-dark',
                                ][$status] ?? 'bg-secondary';
                                $mins = function ($value) {
                                    if ($value === null || $value === '') return '—';
                                    $value = (int) $value;
                                    return intdiv($value, 60) . 'h ' . str_pad((string) ($value % 60), 2, '0', STR_PAD_LEFT) . 'm';
                                };
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $row['excel_row'] }}</td>
                                <td>
                                    {{ $row['employee_name'] ?? trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: '—' }}
                                    @if(!empty($row['department']))
                                        <div class="small text-muted">{{ $row['department'] }}</div>
                                    @endif
                                </td>
                                <td class="font-monospace">{{ $row['hikvision_person_id'] ?? '—' }}</td>
                                <td>{{ $row['date'] ?? '—' }}</td>
                                <td>{{ !empty($row['clock_in']) ? \Carbon\Carbon::parse($row['clock_in'])->format('Y-m-d H:i') : '—' }}</td>
                                <td>{{ !empty($row['clock_out']) ? \Carbon\Carbon::parse($row['clock_out'])->format('Y-m-d H:i') : '—' }}</td>
                                <td>{{ $row['hikvision_status'] ?? '—' }}</td>
                                <td class="text-end">{{ $mins($row['worked_minutes'] ?? null) }}</td>
                                <td class="text-end">{{ $mins($row['late_minutes'] ?? null) }}</td>
                                <td class="text-end">{{ $mins($row['overtime_minutes'] ?? null) }}</td>
                                <td>
                                    <span class="badge {{ $badge }}">{{ $status }}</span>
                                    @if(!empty($row['message']))
                                        <div class="small text-muted mt-1">{{ $row['message'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted py-4">No attendance rows found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" @disabled($rows === [])>
                <i class="bi bi-check-lg me-1"></i> Confirm import
            </button>
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
