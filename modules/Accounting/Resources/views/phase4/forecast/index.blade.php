@extends('accounting::phase2._page')
@section('page_body')
<form method="POST" action="{{ url('accounting/phase4/cash-forecast') }}" class="row g-2">@csrf
    <div class="col-md-3"><input name="name" class="form-control" placeholder="Scenario name" required></div>
    <div class="col-md-2"><select name="scenario_type" class="form-select"><option value="base">Base</option><option value="optimistic">Optimistic</option><option value="conservative">Conservative</option></select></div>
    <div class="col-md-2"><input type="number" name="horizon_days" class="form-control" value="30" min="7"></div>
    <div class="col-auto"><button class="btn btn-primary">Run forecast</button></div>
</form>
@endsection