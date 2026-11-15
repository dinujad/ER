@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white shadow-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="mb-1 text-white font-weight-bolder">Jobs</h3>
                            <p class="mb-0 text-white opacity-8">{{ $section->name }} — Supervisor: {{ $employee->name }}</p>
                        </div>
                        <a href="{{ route('supervisor.dashboard') }}" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ongoing jobs --}}
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Ongoing jobs ({{ $ongoing->count() }})</h5>
        </div>
    </div>
    <div class="row">
        @forelse($ongoing as $job)
        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary">{{ $job->job_number }}</span>
                    <span class="badge bg-warning text-dark">{{ \App\Models\ManufacturingJob::statusLabel($job->status) }}</span>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Style:</strong> {{ $job->style_ref ?? '—' }}</p>
                    <p class="mb-1 text-secondary small">Batch: {{ $job->batch_ref ?? '—' }}</p>
                    @if($section->slug === 'cutting')
                        <hr class="my-2">
                        <p class="mb-0 small text-muted">Cutting records will appear here after you record cutting.</p>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">No ongoing jobs.</div>
        </div>
        @endforelse
    </div>

    {{-- Completed jobs (with full cutting details for cutting section) --}}
    <div class="row mb-2 mt-4">
        <div class="col-12">
            <h5 class="mb-3">Completed jobs ({{ $completed->count() }})</h5>
        </div>
    </div>
    <div class="row">
        @forelse($completed as $job)
        <div class="col-12 mb-3">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="badge bg-success">{{ $job->job_number }}</span>
                    <span class="badge bg-secondary">{{ \App\Models\ManufacturingJob::statusLabel($job->status) }}</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Style ref:</strong> {{ $job->style_ref ?? '—' }}</p>
                            <p class="mb-1"><strong>Batch ref:</strong> {{ $job->batch_ref ?? '—' }}</p>
                            <p class="mb-1"><strong>Issue date:</strong> {{ $job->issue_date ? $job->issue_date->format('d M Y') : '—' }}</p>
                        </div>
                        @if($section->slug === 'cutting')
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Cutting details</h6>
                            <table class="table table-sm table-bordered mb-0">
                                <tr><td>Fabric used (m)</td><td>{{ $job->fabric_used_qty ?? '—' }}</td></tr>
                                <tr><td>Fabric waste (m)</td><td>{{ $job->fabric_waste_qty ?? '—' }}</td></tr>
                                <tr><td>Total cut pieces</td><td>{{ $job->total_cut_pieces ?? '—' }}</td></tr>
                                <tr><td>Reject pieces</td><td>{{ $job->cutting_reject_qty ?? '—' }}</td></tr>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-light border">No completed jobs yet.</div>
        </div>
        @endforelse
    </div>
</div>
@endsection
