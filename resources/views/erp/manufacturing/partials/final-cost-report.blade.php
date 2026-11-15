@php
    $canSeeCosts = auth()->user()?->can_access('manufacturing.costs');
    $p = $finalCost['planned'] ?? [];
    $a = $finalCost['actual'] ?? [];
    $v = $finalCost['variance'] ?? [];
    $s = $finalCost['selling'] ?? [];
    $ub = $a['unit_breakdown'] ?? [];
@endphp
@if(!$canSeeCosts)
    <p class="text-muted small mb-0">Final cost report is visible to administrators only.</p>
@else
<div class="card border-primary mb-4">
    <div class="card-header bg-primary bg-opacity-10 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <strong>Final Cost Report</strong>
            <div class="small text-muted mb-0">Recipe (planned) vs actual production cost — product complete වෙනකොට admin review</div>
        </div>
        @if(!empty($finalCost['is_converted_to_product']))
            <span class="badge bg-success">Converted to product · {{ number_format($finalCost['good_completed_qty'] ?? 0) }} pcs</span>
        @else
            <span class="badge bg-warning text-dark">Pending conversion</span>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="small text-muted">Recipe (planned) / pc</div>
                    @if($p['has_recipe'] ?? false)
                        <div class="fs-5 fw-bold">Rs {{ number_format($p['per_unit'] ?? 0, 2) }}</div>
                        <small class="text-muted d-block">{{ $p['recipe_name'] ?? 'Recipe' }} · batch {{ number_format($p['recipe_output_qty'] ?? 0) }} pcs</small>
                        <small class="text-muted">Total recipe Rs {{ number_format($p['total_recipe'] ?? 0, 2) }}</small>
                    @else
                        <div class="text-warning fw-semibold">No recipe linked</div>
                        <small class="text-muted">Link a recipe to this product for planned cost comparison.</small>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted">Actual cost / pc (incl. overhead)</div>
                    <div class="fs-5 fw-bold text-primary">Rs {{ number_format($a['per_unit'] ?? 0, 2) }}</div>
                    <small class="text-muted">{{ number_format($finalCost['output_qty'] ?? 0) }} pcs produced</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100 {{ ($v['total'] ?? 0) > 0 ? 'border-danger' : 'border-success' }}">
                    <div class="small text-muted">Variance (actual − planned)</div>
                    <div class="fs-5 fw-bold {{ ($v['total'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                        Rs {{ number_format($v['per_unit'] ?? 0, 2) }}/pc
                    </div>
                    @if($v['percent'] !== null)
                        <small class="text-muted">{{ $v['percent'] >= 0 ? '+' : '' }}{{ $v['percent'] }}% vs planned scale</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th colspan="2">Planned (Recipe)</th></tr></thead>
                        <tbody>
                            <tr><td>Ingredients</td><td class="text-end">Rs {{ number_format($p['ingredients_cost'] ?? 0, 2) }}</td></tr>
                            <tr><td>Other (sewing, etc.)</td><td class="text-end">Rs {{ number_format($p['other_costs'] ?? 0, 2) }}</td></tr>
                            <tr class="table-light"><th>Scaled to this job</th><th class="text-end">Rs {{ number_format($p['scaled_total'] ?? 0, 2) }}</th></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th colspan="2">Actual (Job)</th></tr></thead>
                        <tbody>
                            <tr><td>Fabric</td><td class="text-end">Rs {{ number_format($a['fabric'] ?? 0, 2) }}</td></tr>
                            <tr><td>Accessories</td><td class="text-end">Rs {{ number_format($a['accessory'] ?? 0, 2) }}</td></tr>
                            <tr><td>Added costs</td><td class="text-end">Rs {{ number_format($a['additional_total'] ?? 0, 2) }}</td></tr>
                            <tr><td>Overhead</td><td class="text-end">Rs {{ number_format($a['overhead_total'] ?? 0, 2) }}</td></tr>
                            <tr class="table-light"><th>Total actual</th><th class="text-end">Rs {{ number_format($a['total'] ?? 0, 2) }}</th></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if(!empty($ub))
            <div class="border rounded p-3 mb-3 bg-light small">
                <strong>Actual cost sheet / pc</strong>
                <div class="d-flex flex-wrap gap-3 mt-2">
                    <span>Fabric Rs {{ number_format($ub['fabric'] ?? 0, 2) }}</span>
                    <span>Trims Rs {{ number_format($ub['accessory'] ?? 0, 2) }}</span>
                    <span>Sewing Rs {{ number_format($ub['sewing'] ?? 0, 2) }}</span>
                    <span>Overhead Rs {{ number_format($ub['overhead'] ?? 0, 2) }}</span>
                    <span class="fw-bold">Total Rs {{ number_format($ub['total'] ?? 0, 2) }}</span>
                </div>
            </div>
        @endif

        @if(!empty($s['price']))
            <div class="border rounded p-3 mb-0">
                <div class="row g-2">
                    <div class="col-md-4"><span class="text-muted">Selling price</span><div class="fw-semibold">Rs {{ number_format($s['price'], 2) }}</div></div>
                    <div class="col-md-4"><span class="text-muted">Profit / pc (planned cost)</span><div class="fw-semibold">Rs {{ number_format($s['profit_per_unit_planned'] ?? 0, 2) }}</div></div>
                    <div class="col-md-4"><span class="text-muted">Profit / pc (actual cost)</span><div class="fw-semibold text-success">Rs {{ number_format($s['profit_per_unit_actual'] ?? 0, 2) }}</div></div>
                </div>
            </div>
        @endif

        <p class="small text-muted mt-3 mb-0">
            Product convert කරනකොට <code>cost_price</code> actual cost / pc එකෙන් set වෙනවා. Recipe update කළාම planned cost refresh වෙනවා — future: variation-level &amp; multi-batch reports extend කරන්න පුළුවන්.
        </p>
    </div>
</div>
@endif
