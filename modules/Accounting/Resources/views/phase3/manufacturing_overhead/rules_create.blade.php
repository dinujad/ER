@extends('accounting::phase2._page')
@php $pageTitle = 'New overhead rule'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/manufacturing-overhead/rules') }}" class="row g-3">@csrf
    <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Base</label><select name="allocation_base" class="form-select">
        <option value="production_qty">Production qty</option><option value="labor_cost">Labor cost</option>
        <option value="labor_hours">Labor hours (from labour costs)</option></select></div>
    <div class="col-md-4"><label class="form-label">Rate</label><input name="rate" type="number" step="0.01" class="form-control" value="1"></div>
    <div class="col-md-4"><label class="form-label">Pool account ID</label><input name="overhead_pool_account_id" class="form-control" value="{{ $defaultPool }}"></div>
    <div class="col-md-4"><label class="form-label">WIP account ID</label><input name="wip_account_id" class="form-control" value="{{ $defaultWip }}"></div>
    <div class="col-md-4"><label class="form-label">From</label><input name="effective_from" type="date" class="form-control" value="{{ date('Y-m-01') }}"></div>
    <div class="col-12"><button class="btn btn-primary">Save</button></div>
</form>
@endsection