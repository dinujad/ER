@php
    $canSeeCosts = auth()->user()?->can_access('manufacturing.costs');
    $b = $breakdown ?? $job->costBreakdown();
    $ub = $b['unit_breakdown'] ?? [];
@endphp
@if(!$canSeeCosts)
    <p class="text-muted small mb-0">Cost amounts are visible to administrators only.</p>
@else
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="border rounded p-3 text-center h-100">
            <div class="small text-muted">Fabric Cost</div>
            <div class="fw-bold">LKR {{ number_format($b['fabric'], 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded p-3 text-center h-100">
            <div class="small text-muted">Accessories Cost</div>
            <div class="fw-bold">LKR {{ number_format($b['accessory'], 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded p-3 text-center h-100 bg-light">
            <div class="small text-muted">Material Subtotal</div>
            <div class="fw-bold text-primary">LKR {{ number_format($b['material_total'], 2) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="border rounded p-3 text-center h-100 bg-light">
            <div class="small text-muted">Added Production Costs</div>
            <div class="fw-bold text-warning">LKR {{ number_format($b['additional_total'], 2) }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="border rounded p-3 text-center bg-primary bg-opacity-10">
            <div class="small text-muted">Production Cost (direct + added)</div>
            <div class="fs-5 fw-bold text-primary">LKR {{ number_format($b['total'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-3 text-center bg-warning bg-opacity-10">
            <div class="small text-muted">Overhead Cost</div>
            <div class="fs-5 fw-bold text-warning">LKR {{ number_format($b['overhead_total'] ?? 0, 2) }}</div>
            <small class="text-muted d-block">
                Rs {{ number_format($b['overhead_per_piece'] ?? 0, 2) }}/pc
                @if(!empty($b['overhead_period_label']))
                    · {{ $b['overhead_period_label'] }} simple average
                @endif
            </small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-3 text-center bg-dark bg-opacity-10">
            <div class="small text-muted">Total Cost (incl. overhead)</div>
            <div class="fs-5 fw-bold">LKR {{ number_format($b['total_with_overhead'] ?? $b['total'], 2) }}</div>
            <small class="text-muted">Based on {{ number_format($b['output_qty']) }} pcs</small>
        </div>
    </div>
</div>

@if(!empty($ub))
    <div class="border rounded p-3 mb-3 bg-light">
        <div class="fw-semibold mb-2">Cost sheet per piece</div>
        <div class="row g-2 small">
            <div class="col-6 col-md-3 d-flex justify-content-between">
                <span class="text-muted">Fabric</span>
                <span class="fw-semibold">Rs {{ number_format($ub['fabric'] ?? 0, 2) }}</span>
            </div>
            <div class="col-6 col-md-3 d-flex justify-content-between">
                <span class="text-muted">Thread / buttons</span>
                <span class="fw-semibold">Rs {{ number_format($ub['accessory'] ?? 0, 2) }}</span>
            </div>
            <div class="col-6 col-md-3 d-flex justify-content-between">
                <span class="text-muted">Sewing labour</span>
                <span class="fw-semibold">Rs {{ number_format($ub['sewing'] ?? 0, 2) }}</span>
            </div>
            <div class="col-6 col-md-3 d-flex justify-content-between">
                <span class="text-muted">Overhead</span>
                <span class="fw-semibold">Rs {{ number_format($ub['overhead'] ?? 0, 2) }}</span>
            </div>
        </div>
        <hr class="my-2">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Total cost / pc</span>
            <span class="fs-5 fw-bold text-primary">Rs {{ number_format($ub['total'] ?? 0, 2) }}</span>
        </div>
        @if(!empty($b['selling_price']))
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <span class="text-muted">Selling price</span>
                <span class="fw-semibold">Rs {{ number_format($b['selling_price'], 2) }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <span class="fw-semibold">Net profit / pc</span>
                <span class="fs-5 fw-bold {{ ($b['profit_per_unit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    Rs {{ number_format($b['profit_per_unit'] ?? 0, 2) }}
                </span>
            </div>
            <small class="text-muted d-block mt-1">
                Net profit = Selling price − Total cost (fabric + trims + sewing + overhead).
                @if(($b['overhead_total'] ?? 0) > 0)
                    Overhead is posted to Accounting (DR WIP 1320, CR Expense 6000) when finished goods are received.
                @endif
            </small>
        @else
            <small class="text-muted d-block mt-2">Set a selling price on the linked product to see net profit per piece.</small>
        @endif
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <div class="border rounded p-3 text-center bg-success bg-opacity-10">
            <div class="small text-muted">Cost Per Piece (incl. overhead)</div>
            <div class="fs-5 fw-bold text-success">LKR {{ number_format($b['cost_per_unit_with_overhead'] ?? $b['cost_per_unit'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-6">
        @if(!empty($b['additional_by_category']))
            <div class="border rounded p-3 small h-100">
                <div class="fw-semibold mb-2">Added costs by type</div>
                @foreach($b['additional_by_category'] as $cat)
                    <div class="d-flex justify-content-between">
                        <span>{{ $cat['label'] }}</span>
                        <span>LKR {{ number_format($cat['amount'], 2) }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@if(($b['additional_lines'] ?? collect())->isNotEmpty())
    <div class="table-responsive mt-3">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th class="text-end">Amount (LKR)</th>
                    @if($showActions ?? false)
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($b['additional_lines'] as $line)
                    <tr>
                        <td class="small">{{ $line->created_at->format('d M Y') }}</td>
                        <td><span class="badge bg-secondary">{{ $line->categoryLabel() }}</span></td>
                        <td>{{ $line->description }}</td>
                        <td class="text-end fw-semibold">{{ number_format($line->amount, 2) }}</td>
                        @if($showActions ?? false)
                            <td class="text-end">
                                @canRoute('jobs.costs.destroy')
<form action="{{ route('jobs.costs.destroy', [$job->id, $line->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this cost entry?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0">Remove</button>
                                </form>
@endcanRoute
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="{{ ($showActions ?? false) ? 3 : 3 }}" class="text-end">Added costs total</th>
                    <th class="text-end">LKR {{ number_format($b['additional_total'], 2) }}</th>
                    @if($showActions ?? false)<th></th>@endif
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <p class="text-muted small mt-3 mb-0">No admin-added production costs yet (sewing, washing, transport, etc.).</p>
@endif
@endif
