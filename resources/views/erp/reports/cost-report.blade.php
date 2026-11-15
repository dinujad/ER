@extends('erp.layout')

@section('content')
@php
    $rev = $data['revenue'] ?? [];
    $topics = $data['topics'] ?? [];
    $sum = $data['summary'] ?? [];
    $notes = $data['notes'] ?? [];
@endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Reports</a>
            <h4 class="mb-0 fw-bold">{{ $title }}</h4>
            <p class="text-muted small mb-0">Cost sheet + <strong>Simple Average</strong> (Expenses ÷ finished pieces = overhead per piece)</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @php $exportParams = array_merge(request()->query(), ['report' => 'cost-report', 'from' => $from, 'to' => $to]); @endphp
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'pdf'])) }}"><i class="bi bi-file-pdf me-2"></i> PDF</a></li>
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV</a></li>
            </ul>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.show', 'cost-report') }}" class="card mb-4 no-print">
        <div class="card-body d-flex flex-wrap gap-2 align-items-end">
            <div>
                <label class="form-label small mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}" required>
            </div>
            <div>
                <label class="form-label small mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </div>
    </form>

    <p class="text-muted small">Period: <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong> · Revenue basis: <strong>Collections</strong> (payments − refunds)</p>

    @php $avg = $data['simple_average'] ?? []; @endphp
    <div class="card border-warning mb-4">
        <div class="card-header bg-warning bg-opacity-25 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>Simple Average — Overhead per piece</strong>
                <div class="small text-muted mb-0">Total Expenses (ERP Expenses) ÷ finished pieces this period</div>
            </div>
            <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-outline-dark no-print">+ Add Expense</a>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100">
                        <div class="small text-muted">Total Expenses</div>
                        <div class="fs-4 fw-bold">Rs {{ number_format($avg['total_expenses'] ?? 0, 2) }}</div>
                        <div class="small text-muted">Electricity, Water, Rent, …</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100">
                        <div class="small text-muted">Finished pieces</div>
                        <div class="fs-4 fw-bold">{{ number_format($avg['finished_pieces'] ?? 0) }}</div>
                        <div class="small text-muted">
                            @if(($avg['pieces_source'] ?? '') === 'qc_finished')
                                QC finished goods
                            @elseif(($avg['pieces_source'] ?? '') === 'packing_good')
                                Packing (good) qty
                            @else
                                No finished qty in period
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100 bg-warning bg-opacity-10">
                        <div class="small text-muted">Overhead / piece</div>
                        <div class="fs-4 fw-bold text-warning-emphasis">Rs {{ number_format($avg['overhead_per_piece'] ?? 0, 2) }}</div>
                        <div class="small text-muted">{{ $avg['formula'] ?? 'Total Expenses ÷ Finished pieces' }}</div>
                    </div>
                </div>
            </div>

            @if(!empty($avg['expense_lines']))
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Expense category</th>
                                <th class="text-end">Amount (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($avg['expense_lines'] as $line)
                                <tr>
                                    <td>{{ $line['label'] }}</td>
                                    <td class="text-end">{{ number_format($line['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th>Total</th>
                                <th class="text-end">{{ number_format($avg['total_expenses'] ?? 0, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="text-muted small mb-0">No Expenses recorded in this period. Add bills under <a href="{{ route('expenses.index') }}">Expenses</a> (Electricity, Water, Rent…).</p>
            @endif

            @if(($avg['finished_pieces'] ?? 0) <= 0 && ($avg['total_expenses'] ?? 0) > 0)
                <div class="alert alert-warning small mt-3 mb-0">Finished pieces are 0 — cannot compute per-piece overhead. Check QC / packing for this month.</div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Net Collections (Revenue)</div>
                    <div class="fs-4 fw-bold text-success">Rs {{ number_format($rev['net'] ?? 0, 2) }}</div>
                    <div class="small">{{ $rev['orders'] ?? 0 }} orders · Refunds Rs {{ number_format($rev['refunds'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Total Costs (all topics)</div>
                    <div class="fs-4 fw-bold">Rs {{ number_format($sum['total_costs'] ?? 0, 2) }}</div>
                    <div class="small">{{ $sum['job_count'] ?? 0 }} jobs · {{ $sum['expense_count'] ?? 0 }} expenses</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="small text-muted">Net Profit</div>
                    <div class="fs-4 fw-bold {{ ($sum['net_profit'] ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">
                        Rs {{ number_format($sum['net_profit'] ?? 0, 2) }}
                    </div>
                    <div class="small text-muted">Revenue − Cost Sheet total</div>
                </div>
            </div>
        </div>
    </div>

    @foreach($topics as $topic)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <strong>{{ $topic['label'] }}</strong>
                    @if(!empty($topic['label_si']))
                        <span class="text-muted">({{ $topic['label_si'] }})</span>
                    @endif
                    <div class="small text-muted">{{ $topic['description'] }}</div>
                    @if(!empty($topic['subtopics']))
                        <div class="mt-2 small">
                            @foreach($topic['subtopics'] as $sub)
                                <div class="text-muted">
                                    <i class="bi bi-check2 text-success"></i>
                                    <strong>{{ $sub['name'] }}</strong>
                                    <span class="opacity-75">— {{ $sub['hint'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <span class="fw-bold text-nowrap">Rs {{ number_format($topic['total'] ?? 0, 2) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Line</th>
                                <th>Source</th>
                                <th class="text-end">Amount (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topic['lines'] ?? [] as $line)
                                <tr>
                                    <td>{{ $line['label'] }}</td>
                                    <td class="small text-muted">{{ $line['source'] }}</td>
                                    <td class="text-end">{{ number_format($line['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted text-center py-3">No costs in this topic for the period</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($topic['lines']))
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="2">Topic total</th>
                                <th class="text-end">{{ number_format($topic['total'] ?? 0, 2) }}</th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    @if(!empty($notes))
        <div class="alert alert-light border small mb-0">
            <strong>Notes</strong>
            <ul class="mb-0 mt-1">
                @foreach($notes as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
