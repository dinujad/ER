@extends('accounting::phase2._page')
@section('page_body')
<p>As of {{ $report['as_of'] }} · Assets LKR {{ number_format($report['assets'], 2) }} · Liab+Equity LKR {{ number_format($report['liabilities_plus_equity'], 2) }}</p>
<p class="small text-muted">Current-period earnings included in equity: LKR {{ number_format($report['current_earnings'], 2) }}</p>
@endsection