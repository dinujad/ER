@extends('erp.layout')

@section('title', 'Washing Report')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1 fw-bold"><i class="bi bi-file-earmark-bar-graph me-2"></i>Washing Report</h4>
            <p class="text-muted small mb-0">Full history of goods sent to washing, received back, and sent to QC — filter and export as PDF.</p>
        </div>
        <a href="{{ route('manufacturing.washing') }}" class="btn btn-outline-secondary btn-sm">← Washing / Gate Pass</a>
    </div>

    <form method="GET" action="{{ route('manufacturing.washing.report') }}" class="card mb-4">
        <div class="card-header py-2"><strong>Filters</strong></div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-0">Date from</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Date to</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Record type</label>
                    <select name="record_type" class="form-select form-select-sm">
                        <option value="all" @selected($filters['record_type'] === 'all')>All records</option>
                        <option value="gate_pass" @selected($filters['record_type'] === 'gate_pass')>Gate passes only (sent out)</option>
                        <option value="receipt" @selected($filters['record_type'] === 'receipt')>Receipt batches only (came back)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">All statuses</option>
                        @foreach($options['statuses'] as $key => $label)
                            <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Job</label>
                    <select name="job_id" class="form-select form-select-sm">
                        <option value="">All jobs</option>
                        @foreach($options['jobs'] as $j)
                            <option value="{{ $j->id }}" @selected((string) $filters['job_id'] === (string) $j->id)>
                                {{ $j->job_number }} @if($j->style_ref)({{ $j->style_ref }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Washing type</label>
                    <select name="washing_type" class="form-select form-select-sm">
                        <option value="all">All types</option>
                        @foreach($options['washing_types'] as $key => $label)
                            <option value="{{ $key }}" @selected($filters['washing_type'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">Washing factory</label>
                    <input type="text" name="washing_factory" class="form-control form-control-sm" value="{{ $filters['washing_factory'] }}" list="factoryList" placeholder="Factory name">
                    <datalist id="factoryList">
                        @foreach($options['factories'] as $f)
                            <option value="{{ $f }}">
                        @endforeach
                    </datalist>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Vehicle number</label>
                    <input type="text" name="vehicle_number" class="form-control form-control-sm" value="{{ $filters['vehicle_number'] }}" placeholder="e.g. CAB-1234">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Sent from</label>
                    <input type="text" name="sent_from" class="form-control form-control-sm" value="{{ $filters['sent_from'] }}" placeholder="Line / section">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}" placeholder="GP #, queue, job">
                </div>
                <div class="col-md-3 d-flex gap-1 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply filters</button>
                    <a href="{{ route('manufacturing.washing.report') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <a href="{{ route('manufacturing.washing.report', array_merge($filters, ['export' => 'pdf'])) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card h-100 border-primary">
                <div class="card-body py-2">
                    <div class="small text-muted">Gate passes</div>
                    <div class="fs-4 fw-bold text-primary">{{ $summary['gate_pass_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body py-2">
                    <div class="small text-muted">Receipt batches</div>
                    <div class="fs-4 fw-bold">{{ $summary['receipt_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body py-2">
                    <div class="small text-muted">Total sent (pcs)</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary['total_sent']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100 border-success">
                <div class="card-body py-2">
                    <div class="small text-muted">Total received back</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($summary['total_returned']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100 border-warning">
                <div class="card-body py-2">
                    <div class="small text-muted">Pending at wash</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format($summary['pending_at_wash']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body py-2">
                    <div class="small text-muted">Rows shown</div>
                    <div class="fs-4 fw-bold">{{ $rows->count() }}</div>
                    <div class="small text-muted">max 2000</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>Detailed records</strong>
            <span class="small text-muted">{{ $filters['date_from'] }} — {{ $filters['date_to'] }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>GP / Batch</th>
                            <th>Date</th>
                            <th>Job</th>
                            <th>Product / Style</th>
                            <th>Queue</th>
                            <th class="text-end">Sent</th>
                            <th class="text-end">Received</th>
                            <th class="text-end">Pending</th>
                            <th>Status</th>
                            <th>Wash type</th>
                            <th>Vehicle</th>
                            <th>Factory</th>
                            <th>Sent from</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $isReceipt = $row->isReceiptBatch();
                                $sent = $isReceipt ? (int) $row->parent?->quantity_sent : (int) $row->quantity_sent;
                                $received = $isReceipt ? (int) $row->quantity_sent : (int) ($row->quantity_returned ?? 0);
                                $pending = $isReceipt ? 0 : $row->pendingAtWashQuantity();
                            @endphp
                            <tr class="{{ $isReceipt ? 'table-light' : '' }}">
                                <td>
                                    @if($isReceipt)
                                        <span class="badge bg-info text-dark">Receipt</span>
                                    @else
                                        <span class="badge bg-primary">Gate Pass</span>
                                    @endif
                                </td>
                                <td class="font-monospace fw-semibold">
                                    GP-{{ $row->id }}
                                    @if($isReceipt && $row->parent_id)
                                        <div class="small text-muted fw-normal">Parent: GP-{{ $row->parent_id }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $row->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    @if($row->job)
                                        <a href="{{ route('jobs.show', $row->job) }}">{{ $row->job->job_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small">
                                    {{ $row->job?->product?->name ?? '—' }}
                                    @if($row->job?->style_ref)<br><span class="text-muted">{{ $row->job->style_ref }}</span>@endif
                                </td>
                                <td>{{ $row->batch_no ?? '—' }}</td>
                                <td class="text-end font-monospace">{{ $isReceipt ? '—' : number_format($sent) }}</td>
                                <td class="text-end font-monospace text-success">{{ number_format($received) }}</td>
                                <td class="text-end font-monospace {{ $pending > 0 ? 'text-warning fw-semibold' : '' }}">{{ $isReceipt ? '—' : number_format($pending) }}</td>
                                <td><span class="badge bg-secondary">{{ $row->status_label }}</span></td>
                                <td class="small">{{ $row->washing_type ? $row->washing_type_label : '—' }}</td>
                                <td class="small">{{ $row->vehicle_number ?? '—' }}</td>
                                <td class="small">{{ $row->washing_factory_name ?? '—' }}</td>
                                <td class="small">{{ Str::limit($row->sent_from, 28) }}</td>
                                <td class="text-nowrap">
                                    @if(!$isReceipt)
                                        <a href="{{ route('manufacturing.washing.print', ['id' => $row->id, 'format' => 'pdf']) }}" class="btn btn-sm btn-outline-secondary" title="Gate pass PDF">PDF</a>
                                    @endif
                                </td>
                            </tr>
                            @if(!$isReceipt && $row->children->isNotEmpty())
                                @foreach($row->children as $child)
                                    <tr class="table-light">
                                        <td><span class="badge bg-info text-dark ms-2">↳ Receipt</span></td>
                                        <td class="font-monospace small">GP-{{ $child->id }}</td>
                                        <td class="small">{{ $child->created_at->format('d M Y H:i') }}</td>
                                        <td colspan="4" class="small text-muted">{{ $child->batch_no }}</td>
                                        <td class="text-end">—</td>
                                        <td class="text-end text-success font-monospace">{{ number_format($child->quantity_sent) }}</td>
                                        <td>—</td>
                                        <td><span class="badge bg-light text-dark border">{{ $child->status_label }}</span></td>
                                        <td colspan="4"></td>
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr><td colspan="15" class="text-center text-muted py-5">No washing records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
