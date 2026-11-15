@extends('erp.layout')

@section('content')
@php
    $rev = $data['revenue'] ?? [];
    $sum = $data['summary'] ?? [];
    $ledger = $data['ledger'] ?? [];
    $sourceTotals = $data['source_totals'] ?? [];
    $topicTotals = $data['topic_totals'] ?? [];
    $sourceOptions = $data['source_options'] ?? [];
    $topicOptions = $data['topic_options'] ?? [];
    $filters = $data['filters'] ?? [];
@endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Reports</a>
            <h4 class="mb-0 fw-bold">{{ $title }}</h4>
            <p class="text-muted small mb-0">All system expenses &amp; costs in one place — filter by date range, source, and topic · Net Profit included</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @php $exportParams = array_merge(request()->query(), ['report' => 'cost-analyst', 'from' => $from, 'to' => $to]); @endphp
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'pdf'])) }}"><i class="bi bi-file-pdf me-2"></i> PDF</a></li>
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV</a></li>
            </ul>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.show', 'cost-analyst') }}" class="card mb-4 no-print">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-0">Source</label>
                    <select name="source" class="form-select form-select-sm">
                        @foreach($sourceOptions as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['source'] ?? 'all') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-0">Cost topic</label>
                    <select name="topic" class="form-select form-select-sm">
                        <option value="all" @selected(($filters['topic'] ?? 'all') === 'all')>All topics</option>
                        @foreach($topicOptions as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['topic'] ?? 'all') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                </div>
            </div>
        </div>
    </form>

    <p class="text-muted small">Period: <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong>
        @if(($filters['source'] ?? 'all') !== 'all')
            · Source: <strong>{{ $sourceOptions[$filters['source']] ?? $filters['source'] }}</strong>
        @endif
        @if(($filters['topic'] ?? 'all') !== 'all')
            · Topic: <strong>{{ $topicOptions[$filters['topic']] ?? $filters['topic'] }}</strong>
        @endif
    </p>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Net Collections (Revenue)</div>
                    <div class="fs-5 fw-bold text-success">Rs {{ number_format($rev['net'] ?? 0, 2) }}</div>
                    <div class="small">{{ $rev['orders'] ?? 0 }} orders</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Ledger lines (filtered)</div>
                    <div class="fs-5 fw-bold">Rs {{ number_format($sum['ledger_total'] ?? 0, 2) }}</div>
                    <div class="small">{{ $sum['ledger_count'] ?? 0 }} lines shown</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Total Costs (cost sheet)</div>
                    <div class="fs-5 fw-bold">Rs {{ number_format($sum['total_costs'] ?? 0, 2) }}</div>
                    <div class="small">All topics aggregated</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Net Profit</div>
                    <div class="fs-5 fw-bold {{ ($sum['net_profit'] ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">
                        Rs {{ number_format($sum['net_profit'] ?? 0, 2) }}
                    </div>
                    <div class="small text-muted">Revenue − Cost Sheet</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2"><strong>By source</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Source</th><th class="text-end">Lines</th><th class="text-end">Amount (Rs)</th></tr>
                        </thead>
                        <tbody>
                            @forelse($sourceTotals as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No lines</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header py-2"><strong>By cost topic</strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Topic</th><th class="text-end">Lines</th><th class="text-end">Amount (Rs)</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topicTotals as $row)
                                <tr>
                                    <td class="small">{{ $row['label'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center py-3">No lines</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong>All expense &amp; cost lines</strong>
            <span class="badge bg-secondary">{{ count($ledger) }} lines</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Category</th>
                        <th>Topic</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th>Person</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th>Accounting</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledger as $line)
                        <tr>
                            <td class="text-nowrap">{{ $line['date'] }}</td>
                            <td class="small">{{ $line['source'] }}</td>
                            <td>{{ $line['category'] }}</td>
                            <td class="small text-muted">{{ $line['topic_label'] }}</td>
                            <td class="small">{{ $line['description'] }}</td>
                            <td class="small">{{ $line['reference'] ?? '—' }}</td>
                            <td class="small">{{ $line['person'] ?? '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format($line['amount'], 2) }}</td>
                            <td class="small">
                                @if(!empty($line['accounting_linked']))
                                    <span class="badge bg-success">Posted</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-muted text-center py-4">
                                No expense or cost lines in this period.
                                @if(($filters['source'] ?? 'all') === 'expense')
                                    <a href="{{ route('expenses.create') }}">Add an expense</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($ledger) > 0)
                <tfoot class="table-light">
                    <tr>
                        <th colspan="7" class="text-end">Ledger total (filtered)</th>
                        <th class="text-end">Rs {{ number_format($sum['ledger_total'] ?? 0, 2) }}</th>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @php $avg = $data['simple_average'] ?? []; @endphp
    @if(($avg['total_expenses'] ?? 0) > 0)
        <div class="card border-warning mb-4">
            <div class="card-header bg-warning bg-opacity-25">
                <strong>Simple Average — Overhead / piece</strong>
                <span class="small text-muted ms-2">Rs {{ number_format($avg['overhead_per_piece'] ?? 0, 2) }}/pc · {{ number_format($avg['finished_pieces'] ?? 0) }} finished pcs</span>
            </div>
        </div>
    @endif

    @if(!empty($data['notes']))
        <div class="alert alert-light border small mb-0">
            <strong>Notes</strong>
            <ul class="mb-0 mt-1">
                @foreach($data['notes'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
