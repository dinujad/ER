@extends('erp.layout')

@section('content')
@php
    $f = $data['filters'] ?? [];
    $t = $data['totals'] ?? [];
@endphp
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 no-print">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left me-1"></i> Reports</a>
            <h4 class="mb-0 fw-bold">{{ $title }}</h4>
            <p class="text-muted small mb-0">Transport, Sewing, Washing සහ අනෙක් production costs — job-wise &amp; category-wise breakdown</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">Export</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @php $exportParams = array_merge(request()->query(), ['report' => 'cost-analysis', 'from' => $from, 'to' => $to]); @endphp
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'pdf'])) }}"><i class="bi bi-file-pdf me-2"></i> PDF</a></li>
                <li><a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}"><i class="bi bi-file-earmark-spreadsheet me-2"></i> CSV</a></li>
            </ul>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.show', 'cost-analysis') }}" class="card mb-4 no-print">
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
                    <label class="form-label small mb-0">Scope</label>
                    <select name="scope" class="form-select form-select-sm">
                        <option value="activity" @selected(($f['scope'] ?? '') === 'activity')>Any activity</option>
                        <option value="issued" @selected(($f['scope'] ?? '') === 'issued')>Issued in period</option>
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
                    <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                </div>
            </div>
        </div>
    </form>

    <div class="alert alert-info small">
        <strong>Transport cost කොහෙන්ද?</strong>
        Job එකක <em>Costs</em> page එකෙන් category <strong>Transport</strong> select කරලා add කළ amount මෙතන පෙන්වනවා.
        Recipe එකේ Other Costs (planned) යටතේ Transport ලෙස add කළේ නම් පහළ "Recipe Planned Costs" section එකේ පෙන්වනවා.
    </div>

    <div class="row g-2 mb-4">
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">Jobs</div><div class="fs-5 fw-bold">{{ $data['job_count'] ?? 0 }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">Fabric</div><div class="fs-5 fw-bold">Rs {{ number_format($t['fabric'] ?? 0, 0) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">Accessory</div><div class="fs-5 fw-bold">Rs {{ number_format($t['accessory'] ?? 0, 0) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2 border-warning"><div class="small text-muted">Transport</div><div class="fs-5 fw-bold text-warning">Rs {{ number_format($data['category_totals']['transport']['amount'] ?? 0, 0) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2"><div class="small text-muted">All Added</div><div class="fs-5 fw-bold">Rs {{ number_format($t['additional'] ?? 0, 0) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="card text-center py-2 border-primary"><div class="small text-muted">Total Production</div><div class="fs-5 fw-bold text-primary">Rs {{ number_format($t['production'] ?? 0, 0) }}</div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Added Costs by Category (Actual — Jobs)</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Category</th><th class="text-end">Amount (Rs)</th></tr></thead>
                <tbody>
                    @foreach($data['category_totals'] ?? [] as $cat)
                        @if(($cat['amount'] ?? 0) > 0)
                        <tr>
                            <td>{{ $cat['label'] }}</td>
                            <td class="text-end fw-semibold">{{ number_format($cat['amount'], 2) }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Job-wise Cost Breakdown</strong></div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Job #</th><th>Product</th>
                        <th class="text-end">Fabric</th><th class="text-end">Accessory</th>
                        <th class="text-end">Transport</th><th class="text-end">Sewing</th><th class="text-end">Washing</th>
                        <th class="text-end">Other Added</th><th class="text-end">Overhead</th><th class="text-end">Total</th><th class="text-end">Cost/Pc</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['job_rows'] ?? [] as $row)
                        @php $cats = $row['additional_by_category'] ?? []; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $row['job']->job_number }}</td>
                            <td class="small">{{ $row['job']->product?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($row['fabric'], 2) }}</td>
                            <td class="text-end">{{ number_format($row['accessory'], 2) }}</td>
                            <td class="text-end text-warning">{{ number_format($cats['transport']['amount'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($cats['sewing']['amount'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($cats['washing']['amount'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($row['additional_total'], 2) }}</td>
                            <td class="text-end text-warning">{{ number_format($row['overhead_total'] ?? 0, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row['total'], 2) }}</td>
                            <td class="text-end text-success">{{ number_format($row['cost_per_unit'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-muted text-center py-3">No jobs in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>All Additional Cost Lines</strong></div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light"><tr><th>Job #</th><th>Category</th><th>Description</th><th class="text-end">Amount (Rs)</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($data['additional_lines'] ?? [] as $line)
                        <tr>
                            <td>{{ $line['job_number'] }}</td>
                            <td><span class="badge bg-secondary">{{ $line['category_label'] }}</span></td>
                            <td>{{ $line['description'] ?? '—' }}</td>
                            <td class="text-end">{{ number_format($line['amount'], 2) }}</td>
                            <td>{{ $line['created_at']?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No additional costs recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Recipe Planned Other Costs</strong> <small class="text-muted">(estimate — recipe add/edit කරනකොට දාන costs)</small></div>
        <div class="card-body">
            @if(($data['recipe_other_by_description'] ?? collect())->isNotEmpty())
                <div class="row mb-3">
                    @foreach($data['recipe_other_by_description'] as $desc => $amt)
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 text-center">
                                <div class="small text-muted">{{ $desc }}</div>
                                <div class="fw-bold">Rs {{ number_format($amt, 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light"><tr><th>Recipe</th><th>Product</th><th>Description</th><th class="text-end">Amount (Rs)</th></tr></thead>
                    <tbody>
                        @forelse($data['recipe_other_lines'] ?? [] as $line)
                            <tr>
                                <td>{{ $line->recipe?->name ?? '—' }}</td>
                                <td>{{ $line->recipe?->product?->name ?? '—' }}</td>
                                <td>{{ $line->description }}</td>
                                <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No recipe other costs defined.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
