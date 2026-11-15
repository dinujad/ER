@extends('accounting::phase2._page')
@section('page_body')
<form method="POST" action="{{ url('accounting/phase4/tax/codes') }}" class="row g-2 mb-3">@csrf
    <div class="col-md-2"><input name="code" class="form-control" placeholder="Code" required></div>
    <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
    <div class="col-md-2"><select name="tax_type" class="form-select"><option value="vat">VAT</option><option value="withholding">Withholding</option><option value="other">Other</option></select></div>
    <div class="col-md-2"><input name="rate" type="number" step="0.0001" class="form-control" placeholder="Rate %" required></div>
    <div class="col-md-2"><input name="effective_from" type="date" class="form-control" required></div>
    <div class="col-auto"><button class="btn btn-primary">Add code</button></div>
</form>
@endsection