<div class="col-md-4">
    <label class="form-label">Category</label>
    <select name="category_id" class="form-select form-select-sm" required>
        @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected($asset->category_id==$c->id)>{{ $c->code }} — {{ $c->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-4"><label class="form-label">Name</label><input name="name" value="{{ $asset->name }}" class="form-control form-control-sm" required></div>
<div class="col-md-4"><label class="form-label">Asset cost</label><input type="number" step="0.01" name="asset_cost" value="{{ $asset->asset_cost }}" class="form-control form-control-sm" required></div>
<div class="col-md-3"><label class="form-label">Capitalized add-ons</label><input type="number" step="0.01" name="capitalized_costs" value="{{ $asset->capitalized_costs }}" class="form-control form-control-sm"></div>
<div class="col-md-3"><label class="form-label">Salvage</label><input type="number" step="0.01" name="salvage_value" value="{{ $asset->salvage_value }}" class="form-control form-control-sm"></div>
<div class="col-md-3"><label class="form-label">Useful life (months)</label><input type="number" name="useful_life_months" value="{{ $asset->useful_life_months }}" class="form-control form-control-sm"></div>
<div class="col-md-3"><label class="form-label">In-service date</label><input type="date" name="in_service_date" value="{{ optional($asset->in_service_date)->format('Y-m-d') }}" class="form-control form-control-sm"></div>
<div class="col-md-3">
    <label class="form-label">Method</label>
    <select name="depreciation_method" class="form-select form-select-sm">
        @foreach(['straight_line','declining_balance','none'] as $m)
            <option value="{{ $m }}" @selected($asset->depreciation_method===$m)>{{ $m }}</option>
        @endforeach
    </select>
</div>
@if(isset($costCenters) && $costCenters->isNotEmpty())
<div class="col-md-3">
    <label class="form-label">Cost center</label>
    <select name="cost_center_id" class="form-select form-select-sm"><option value="">—</option>
        @foreach($costCenters as $cc)<option value="{{ $cc->id }}" @selected($asset->cost_center_id==$cc->id)>{{ $cc->code }}</option>@endforeach
    </select>
</div>
@endif