@extends('accounting::phase2._page')
@section('page_body')
<form method="POST" action="{{ url('accounting/phase4/scheduled-reports') }}" class="row g-2 mb-3">@csrf
    <div class="col-md-3"><select name="report_type" class="form-select">@foreach($types as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="frequency" class="form-select"><option value="monthly">Monthly</option><option value="weekly">Weekly</option><option value="daily">Daily</option></select></div>
    <div class="col-md-2"><input name="next_run_at" type="datetime-local" class="form-control" required></div>
    <div class="col-auto"><button class="btn btn-primary">Schedule</button></div>
</form>
@endsection