@extends('erp.layout')

@section('content')
<h4 class="mb-3">HR settings</h4>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <p class="mb-1"><strong>Timezone</strong></p>
            <p>{{ $timezone }}</p>
            <div class="d-grid gap-2">
                <a class="btn btn-outline-primary" href="{{ route('shifts.index') }}">Attendance rules and OT rates</a>
                <a class="btn btn-outline-primary" href="{{ route('roles.index') }}">Roles and permissions</a>
                <a class="btn btn-outline-primary" href="{{ route('announcements.index') }}">Notices</a>
                <a class="btn btn-outline-secondary" href="{{ route('attendance.hik-connect-import.create') }}">Attendance import history</a>
            </div>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header"><h6 class="mb-0">Audit log</h6></div>
            <table class="table mb-0">
                <thead class="table-light"><tr><th>When</th><th>Who</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr><td>{{ \Carbon\Carbon::parse($log->created_at)->format('d M H:i') }}</td><td>{{ $log->name ?? '—' }}</td><td>{{ $log->action }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No HR changes recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="card-footer bg-white">{{ $logs->links() }}</div>
        </div>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header"><h6 class="mb-0">Attendance import history</h6></div>
            <table class="table mb-0">
                <thead class="table-light"><tr><th>File</th><th>Created</th><th>Updated</th><th>Failed</th></tr></thead>
                <tbody>
                    @forelse($imports as $import)
                        <tr><td>{{ $import->filename }}</td><td>{{ $import->created_count }}</td><td>{{ $import->updated_count }}</td><td>{{ $import->failed_count }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No imports yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="card-footer bg-white">{{ $imports->links() }}</div>
        </div>
    </div>
</div>
@endsection
