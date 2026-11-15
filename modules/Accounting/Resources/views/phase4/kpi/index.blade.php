@extends('accounting::phase2._page')
@section('page_body')
<dl class="row">
    <dt class="col-sm-4">Gross margin</dt><dd class="col-sm-8">{{ $kpis['gross_margin_pct'] ?? 'N/A' }}%</dd>
    <dt class="col-sm-4">Current ratio</dt><dd class="col-sm-8">{{ $kpis['current_ratio'] ?? 'N/A' }}</dd>
    <dt class="col-sm-4">Quick ratio</dt><dd class="col-sm-8">{{ $kpis['quick_ratio'] ?? 'N/A' }}</dd>
</dl>
@endsection