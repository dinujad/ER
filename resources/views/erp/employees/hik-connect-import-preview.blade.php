@extends('erp.layout')

@section('title', 'Preview Hik-Connect Import')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Preview Hik-Connect Employees</h1>
            <p class="text-muted small mb-0">
                {{ $originalName }} · header row {{ $headerRow }} ·
                New {{ $counts['new'] ?? 0 }} ·
                Existing {{ $counts['existing'] ?? 0 }} ·
                Conflict {{ $counts['conflict'] ?? 0 }} ·
                Invalid {{ $counts['invalid'] ?? 0 }}
            </p>
        </div>
        <a href="{{ route('employees.hik-connect-import.create') }}" class="btn btn-outline-secondary btn-sm">Upload a different file</a>
    </div>

    <form action="{{ route('employees.hik-connect-import.store') }}" method="POST">
        @csrf
        <div class="card shadow-sm border-0 mb-3">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Excel</th>
                            <th>Hik-Connect ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Link existing employee</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $status = $row['status'] ?? 'invalid';
                                $badge = [
                                    'new' => 'bg-success',
                                    'existing' => 'bg-primary',
                                    'link' => 'bg-info text-dark',
                                    'conflict' => 'bg-danger',
                                    'invalid' => 'bg-warning text-dark',
                                ][$status] ?? 'bg-secondary';
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $row['excel_row'] }}</td>
                                <td class="font-monospace">{{ $row['hikvision_person_id'] ?? '—' }}</td>
                                <td>{{ $row['display_name'] ?? '—' }}</td>
                                <td>{{ $row['department'] ?? '—' }}</td>
                                <td>{{ $row['email'] ?? '—' }}</td>
                                <td>{{ $row['phone'] ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $badge }}">{{ $status }}</span>
                                    @if(!empty($row['message']))
                                        <div class="small text-muted mt-1">{{ $row['message'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($status === 'new')
                                        <select name="links[{{ $row['excel_row'] }}]" class="form-select form-select-sm">
                                            <option value="">Create new employee</option>
                                            @foreach($linkable as $emp)
                                                <option value="{{ $emp->id }}">#{{ $emp->id }} {{ $emp->display_name }} ({{ $emp->nic }})</option>
                                            @endforeach
                                        </select>
                                    @elseif($status === 'existing')
                                        <span class="small text-muted">#{{ $row['employee_id'] }} {{ $row['employee_name'] }}</span>
                                    @else
                                        <span class="small text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No employee rows found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" @disabled($rows === [])>
                <i class="bi bi-check-lg me-1"></i> Confirm import
            </button>
            <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
