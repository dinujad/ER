@extends('erp.layout')

@section('content')
@php
    $f = $data['filters'] ?? [];
    $pt = $data['production']['totals'] ?? [];
    $ws = $data['washing_summary'] ?? [];
    $wf = $data['washing_filters'] ?? [];
    $wopt = $data['washing_options'] ?? [];
@endphp
<style>
@media print {
    .sidebar, .erp-mobile-header, .no-print, .btn, nav, .alert { display: none !important; }
    .content { padding: 0 !important; }
}
.mfg-report .section-title { font-size: 1rem; font-weight: 700; color: #0c4a6e; margin: 1.5rem 0 0.75rem; padding-bottom: 0.35rem; border-bottom: 2px solid #e2e8f0; }
</style>
<div class="container-fluid py-4 mfg-report">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Reports</a>
            <h4 class="mb-0 fw-bold">{{ $title }}</h4>
            <p class="text-muted small mb-0">Jobs, line hourly output, washing gate passes, and QC — full manufacturing activity</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @php $exportParams = array_merge(request()->query(), ['report' => 'manufacturing', 'from' => $from, 'to' => $to]); @endphp
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'pdf'])) }}"><i class="bi bi-file-pdf me-2"></i> PDF</a></li>
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV</a></li>
            </ul>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.show', 'manufacturing') }}" class="card mb-4 no-print">
        <div class="card-header py-2"><strong>Filters</strong></div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Job scope</label>
                    <select name="scope" class="form-select form-select-sm">
                        <option value="activity" @selected(($f['scope'] ?? '') === 'activity')>Any activity in period</option>
                        <option value="issued" @selected(($f['scope'] ?? '') === 'issued')>Issued in period only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Job status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach($data['status_options'] ?? [] as $key => $label)
                            <option value="{{ $key }}" @selected(($f['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Job</label>
                    <select name="job_id" class="form-select form-select-sm">
                        <option value="">All jobs</option>
                        @foreach($data['filter_jobs'] ?? [] as $j)
                            <option value="{{ $j->id }}" @selected((string) ($f['job_id'] ?? '') === (string) $j->id)>{{ $j->job_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $f['search'] ?? '' }}" placeholder="Job #, style">
                </div>
                <div class="col-12"><hr class="my-2"><span class="small text-muted fw-semibold">Washing filters</span></div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Wash record</label>
                    <select name="wash_record_type" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="gate_pass" @selected(request('wash_record_type') === 'gate_pass')>Gate passes</option>
                        <option value="receipt" @selected(request('wash_record_type') === 'receipt')>Receipts</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Wash status</label>
                    <select name="wash_status" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach($wopt['statuses'] ?? [] as $key => $label)
                            <option value="{{ $key }}" @selected(request('wash_status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Washing type</label>
                    <select name="washing_type" class="form-select form-select-sm">
                        <option value="all">All</option>
                        @foreach($wopt['washing_types'] ?? [] as $key => $label)
                            <option value="{{ $key }}" @selected(request('washing_type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Factory</label>
                    <input type="text" name="washing_factory" class="form-control form-control-sm" value="{{ request('washing_factory') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Vehicle</label>
                    <input type="text" name="vehicle_number" class="form-control form-control-sm" value="{{ request('vehicle_number') }}">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply</button>
                    <a href="{{ route('reports.show', 'manufacturing') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-2 mb-4">
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">Jobs</div><div class="fs-5 fw-bold text-primary">{{ $data['job_count'] ?? 0 }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">Line output</div><div class="fs-5 fw-bold text-success">{{ number_format($pt['period_produced'] ?? 0) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">Line target</div><div class="fs-5 fw-bold">{{ number_format($pt['period_target'] ?? 0) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">Washing sent</div><div class="fs-5 fw-bold">{{ number_format($ws['total_sent'] ?? 0) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">Washing received</div><div class="fs-5 fw-bold text-info">{{ number_format($ws['total_returned'] ?? 0) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">QC finished</div><div class="fs-5 fw-bold">{{ number_format($data['qc_finished'] ?? 0) }}</div></div></div>
    </div>

    <div class="card section-card">
        <div class="card-body">
            <h5 class="section-title"><i class="bi bi-briefcase me-2"></i>Manufacturing Jobs</h5>
            @php $canSeeCosts = auth()->user()?->can_access('manufacturing.costs'); @endphp
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Job #</th><th>Style</th><th>Product</th><th>Status</th><th>Issue date</th>
                            <th class="text-end">Cut</th><th class="text-end">Line target</th><th class="text-end">Produced</th><th class="text-end">To wash</th>
                            @if($canSeeCosts)
                            <th class="text-end">Fabric</th><th class="text-end">Access.</th><th class="text-end">Added</th><th class="text-end">Overhead</th><th class="text-end">Total cost</th><th class="text-end">Cost/pc</th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['job_details'] ?? [] as $row)
                            @php $j = $row['job']; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $j->job_number }}</td>
                                <td>{{ $j->style_ref ?? '—' }}</td>
                                <td class="small">{{ $j->product?->name ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $j->status) }}</span></td>
                                <td>{{ $j->issue_date?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">{{ number_format($row['cut_pieces']) }}</td>
                                <td class="text-end">{{ number_format($row['line_target']) }}</td>
                                <td class="text-end text-success">{{ number_format($row['line_produced']) }}</td>
                                <td class="text-end">{{ number_format($row['washing_available']) }}</td>
                                @if($canSeeCosts)
                                <td class="text-end small">{{ number_format($row['fabric_cost'] ?? 0, 2) }}</td>
                                <td class="text-end small">{{ number_format($row['accessory_cost'] ?? 0, 2) }}</td>
                                <td class="text-end small text-warning">{{ number_format($row['additional_cost'] ?? 0, 2) }}</td>
                                <td class="text-end small text-warning">{{ number_format($row['overhead_cost'] ?? 0, 2) }}</td>
                                <td class="text-end fw-semibold">{{ number_format($row['total_cost'] ?? 0, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($row['cost_per_unit'] ?? 0, 2) }}</td>
                                @endif
                                <td class="text-nowrap">
                                    @if($canSeeCosts)
                                    <a href="{{ route('jobs.costs', $j) }}" class="btn btn-sm btn-link py-0">Costs</a>
                                    @endif
                                    <a href="{{ route('jobs.show', $j) }}" class="btn btn-sm btn-link py-0">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canSeeCosts ? 16 : 10 }}" class="text-muted text-center py-3">No jobs in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="section-title"><i class="bi bi-clock-history me-2"></i>Line Production (Hourly Output &amp; Targets)</h5>
            @if(empty($data['production']['job_rows']))
                <p class="text-muted">No line production in period.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Job / Line</th>
                                <th class="text-end">Job target</th>
                                <th class="text-end">Total produced</th>
                                <th class="text-end">Period output</th>
                                <th class="text-end">Period target</th>
                                <th class="text-end">Today out</th>
                                <th class="text-end">Today target</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['production']['job_rows'] as $prow)
                                <tr class="table-secondary">
                                    <td colspan="2"><strong>{{ $prow['job']->job_number }}</strong></td>
                                    <td class="text-end fw-semibold">{{ number_format($prow['job_total_produced']) }}</td>
                                    <td class="text-end text-success fw-semibold">{{ number_format($prow['job_period_produced']) }}</td>
                                    <td colspan="4"></td>
                                </tr>
                                @foreach($prow['lines'] as $line)
                                    <tr>
                                        <td class="ps-4 text-muted">↳ {{ $line['line_name'] }}</td>
                                        <td class="text-end">{{ number_format($line['job_target']) }}</td>
                                        <td class="text-end">{{ number_format($line['total_produced']) }}</td>
                                        <td class="text-end text-success">{{ number_format($line['period_produced']) }}</td>
                                        <td class="text-end">{{ number_format($line['period_target']) }}</td>
                                        <td class="text-end">{{ number_format($line['today_produced']) }}</td>
                                        <td class="text-end">{{ number_format($line['today_target']) }}</td>
                                        <td class="text-end">{{ number_format($line['remaining_job']) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <h5 class="section-title"><i class="bi bi-droplet me-2"></i>Washing — Sent &amp; Received</h5>
            <p class="small text-muted mb-2">
                Gate passes: {{ $ws['gate_pass_count'] ?? 0 }} |
                Receipts: {{ $ws['receipt_count'] ?? 0 }} |
                Pending at wash: {{ number_format($ws['pending_at_wash'] ?? 0) }} pcs
            </p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th><th>GP</th><th>Date</th><th>Job</th><th>Queue</th>
                            <th class="text-end">Sent</th><th class="text-end">Received</th><th>Status</th>
                            <th>Wash type</th><th>Factory</th><th>Vehicle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['washing_rows'] ?? [] as $w)
                            <tr>
                                <td>{{ $w->isReceiptBatch() ? 'Receipt' : 'Gate Pass' }}</td>
                                <td>GP-{{ $w->id }}</td>
                                <td>{{ $w->created_at->format('d M Y') }}</td>
                                <td>{{ $w->job?->job_number ?? '—' }}</td>
                                <td>{{ $w->batch_no ?? '—' }}</td>
                                <td class="text-end">{{ $w->isReceiptBatch() ? '—' : number_format($w->quantity_sent) }}</td>
                                <td class="text-end text-success">{{ number_format($w->isReceiptBatch() ? $w->quantity_sent : ($w->quantity_returned ?? 0)) }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $w->status_label }}</span></td>
                                <td>{{ $w->washing_type ? $w->washing_type_label : '—' }}</td>
                                <td class="small">{{ $w->washing_factory_name ?? '—' }}</td>
                                <td>{{ $w->vehicle_number ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-muted text-center py-3">No washing records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="section-title"><i class="bi bi-check2-square me-2"></i>QC (After Wash)</h5>
            <p class="small text-muted mb-2">
                Checked: {{ number_format($data['qc_total_checked'] ?? 0) }} |
                Finished: {{ number_format($data['qc_finished'] ?? 0) }} |
                Damaged: {{ number_format($data['qc_damaged'] ?? 0) }}
            </p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Job</th><th>Batch</th><th class="text-end">Checked</th><th class="text-end">Finished</th><th class="text-end">Damaged</th></tr>
                    </thead>
                    <tbody>
                        @forelse($data['qc_checks'] ?? [] as $qc)
                            <tr>
                                <td>{{ $qc->created_at->format('d M Y') }}</td>
                                <td>{{ $qc->washingTransfer?->job?->job_number ?? '—' }}</td>
                                <td>{{ $qc->washingTransfer?->batch_no ?? '—' }}</td>
                                <td class="text-end">{{ number_format($qc->total_checked) }}</td>
                                <td class="text-end text-success">{{ number_format($qc->finished_good_qty) }}</td>
                                <td class="text-end text-danger">{{ number_format($qc->damaged_qty) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">No QC records in period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
