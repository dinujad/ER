@extends('accounting::phase2._page')
@section('page_body')
<ul class="list-unstyled">
    @foreach($report['buckets'] as $k => $v)<li>{{ ucfirst($k) }}: LKR {{ number_format($v, 2) }}</li>@endforeach
</ul>
<p class="small text-muted">{{ implode(' ', $report['meta']['assumptions'] ?? []) }}</p>
@endsection