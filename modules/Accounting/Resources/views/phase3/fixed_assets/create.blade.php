@extends('accounting::phase2._page')
@php $pageTitle = 'New Fixed Asset'; $pageSub = 'Draft register entry'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/fixed-assets') }}" class="row g-3">
    @csrf
    <div class="col-md-4">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select form-select-sm" required>
            @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Asset code</label><input name="asset_code" value="{{ $suggestedCode }}" class="form-control form-control-sm"></div>
    <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><label class="form-label">Asset cost (LKR)</label><input type="number" step="0.01" name="asset_cost" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><label class="form-label">Capitalized add-ons</label><input type="number" step="0.01" name="capitalized_costs" value="0" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="form-label">Salvage value</label><input type="number" step="0.01" name="salvage_value" value="0" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="form-label">Useful life (months)</label><input type="number" name="useful_life_months" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="form-label">In-service date</label><input type="date" name="in_service_date" class="form-control form-control-sm"></div>
    <div class="col-md-3">
        <label class="form-label">Depreciation method</label>
        <select name="depreciation_method" class="form-select form-select-sm">
            <option value="straight_line">Straight line</option>
            <option value="declining_balance">Declining balance</option>
            <option value="none">None</option>
        </select>
    </div>
    @if($costCenters->isNotEmpty())
    <div class="col-md-3">
        <label class="form-label">Cost center</label>
        <select name="cost_center_id" class="form-select form-select-sm"><option value="">—</option>
            @foreach($costCenters as $cc)<option value="{{ $cc->id }}">{{ $cc->code }}</option>@endforeach
        </select>
    </div>
    @endif
    <div class="col-12"><button class="btn btn-primary btn-sm">Save draft</button></div>
</form>
@endsection