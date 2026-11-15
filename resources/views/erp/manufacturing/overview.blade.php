@extends('erp.layout')

@section('content')
<style>
    .mfg-hero { background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%); color: #fff; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; }
    .mfg-card { border: none; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: transform 0.2s; }
    .mfg-card:hover { transform: translateY(-2px); }
    .mfg-kpi { font-size: 1.5rem; font-weight: 700; }
    .mfg-kpi-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.03em; }
    .mfg-link { text-decoration: none; color: inherit; display: block; }
    .mfg-link:hover { color: inherit; }
</style>

<div class="mfg-hero d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="h4 mb-1 fw-bold">Manufacturing Overview</h1>
        <p class="mb-0 opacity-90 small">Jobs by stage and quick access</p>
    </div>
    <span class="badge bg-light text-dark">{{ now()->format('l, M j, Y') }}</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card mfg-card h-100">
            <div class="card-body">
                <div class="mfg-kpi text-primary">{{ $totalJobs }}</div>
                <div class="mfg-kpi-label">Total Jobs</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <a href="{{ route('jobs.index') }}" class="mfg-link">
            <div class="card mfg-card h-100 border-primary border-opacity-25">
                <div class="card-body">
                    <div class="mfg-kpi text-secondary">{{ $fabricOrCutting }}</div>
                    <div class="mfg-kpi-label">Fabric / Cutting</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-2">
        <div class="card mfg-card h-100">
            <div class="card-body">
                <div class="mfg-kpi text-info">{{ $lineAssigned }}</div>
                <div class="mfg-kpi-label">Line Assigned</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <a href="{{ route('manufacturing.washing') }}" class="mfg-link">
            <div class="card mfg-card h-100">
                <div class="card-body">
                    <div class="mfg-kpi text-warning">{{ $washing }}</div>
                    <div class="mfg-kpi-label">Washing</div>
                    @if(($pendingGatePasses ?? 0) > 0)
                        <div class="small text-danger fw-semibold mt-1">{{ $pendingGatePasses }} at gate (pending)</div>
                    @endif
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-2">
        <a href="{{ route('manufacturing.qc') }}" class="mfg-link">
            <div class="card mfg-card h-100">
                <div class="card-body">
                    <div class="mfg-kpi" style="color: #7b1fa2;">{{ $afterWashOrPacking }}</div>
                    <div class="mfg-kpi-label">After Wash / Packing</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-2">
        <div class="card mfg-card h-100">
            <div class="card-body">
                <div class="mfg-kpi text-success">{{ $warehouseOrSold }}</div>
                <div class="mfg-kpi-label">Warehouse / Sold</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mfg-card">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Recent Jobs</h5>
                <a href="{{ route('jobs.index') }}" class="btn btn-sm btn-primary">All Jobs</a>
            </div>
            <div class="card-body p-0">
                @if($recentJobs->isEmpty())
                    <p class="text-muted text-center py-4 mb-0">No jobs yet. <a href="{{ route('jobs.create') }}">Create a job (Fabric Issue)</a>.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Job #</th>
                                    <th>Style / Batch</th>
                                    <th>Product</th>
                                    <th>Status</th>
                                    <th>Cut Pieces</th>
                                    <th class="text-end">Est. material</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentJobs as $job)
                                    @php
                                        $isLineActive = in_array($job->status, \App\Models\ManufacturingJob::hourlyEntryStatuses(), true);
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $job->job_number }}</strong></td>
                                        <td>{{ $job->style_ref }} @if($job->batch_ref)<br><small class="text-muted">{{ $job->batch_ref }}</small>@endif</td>
                                        <td>{{ $job->product?->name ?? '—' }}</td>
                                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $job->status) }}</span></td>
                                        <td>{{ $job->total_cut_pieces ?? '—' }}</td>
                                        <td class="text-end font-monospace small">
                                            @php $mCost = $job->estimatedMaterialCost(); @endphp
                                            @if($mCost > 0)
                                                <span class="text-success fw-semibold">Rs {{ number_format($mCost, 2) }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                @if($isLineActive)
                                                    <a href="{{ route('hourly-production.create', ['job_id' => $job->id]) }}"
                                                       class="btn btn-sm btn-info" title="Record hourly output">
                                                        <i class="bi bi-stopwatch"></i> Hourly
                                                    </a>
                                                @endif
                                                <a href="{{ route('jobs.show', $job) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mfg-card">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Quick links</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('jobs.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-list me-2"></i>All Jobs Listing</a>
                    <a href="{{ route('jobs.create') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plus-circle me-2"></i>Fabric Issue (New Job)</a>
                    <hr class="my-2">
                    <span class="text-xs text-uppercase text-muted fw-bold">Production Stages</span>
                    <a href="{{ route('manufacturing.cutting') }}" class="btn btn-outline-secondary btn-sm">Cutting Section</a>
                    <a href="{{ route('hourly-production.create') }}" class="btn btn-outline-secondary btn-sm">Line Production (Hourly)</a>
                    <a href="{{ route('manufacturing.washing') }}" class="btn btn-outline-secondary btn-sm">Washing / Gate Pass</a>
                    <a href="{{ route('manufacturing.qc') }}" class="btn btn-outline-secondary btn-sm">After Wash / Packing (QC)</a>
                    <a href="{{ route('manufacturing.final.index') }}" class="btn btn-outline-warning btn-sm">Final Checking / Receiving</a>
                    <hr class="my-2">
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-graph-up me-2"></i>Manufacturing Reports</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
