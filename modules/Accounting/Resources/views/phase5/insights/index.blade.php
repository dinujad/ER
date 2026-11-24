@extends('accounting::phase2._page')
@section('page_body')
<form method="post" action="{{ url('accounting/phase5/insights/generate') }}" class="row g-2 mb-3">@csrf
    <div class="col-auto"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Generate insights</button></div>
</form>
@foreach($insights as $i)
<div class="border rounded p-3 mb-2">
    <div class="fw-semibold">{{ $i->title }} <span class="badge text-bg-secondary">{{ $i->category }}</span></div>
    <div class="small text-muted">{{ $i->period_from }} → {{ $i->period_to }} · {{ $i->source_metric }} vs {{ $i->comparison_basis }} · {{ $i->certainty }}</div>
    <div>Actual: LKR {{ number_format((float)$i->actual_amount, 2) }}
        @if($i->change_amount !== null) · Change: LKR {{ number_format((float)$i->change_amount, 2) }} @endif
        @if($i->change_pct !== null) ({{ number_format((float)$i->change_pct, 2) }}%) @endif
    </div>
    <div class="mt-1"><strong>Fact:</strong> {{ $i->observed_fact }}</div>
    @if($i->possible_explanation)<div class="small"><strong>Possible explanation:</strong> {{ $i->possible_explanation }}</div>@endif
    @if($i->suggested_action)<div class="small"><strong>Suggested action:</strong> {{ $i->suggested_action }}</div>@endif
    @if($i->drilldown_url)<a class="small" href="{{ $i->drilldown_url }}">Drill down</a>@endif
</div>
@endforeach
@endsection
