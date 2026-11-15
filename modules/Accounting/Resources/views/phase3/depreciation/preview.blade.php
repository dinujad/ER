@extends('accounting::phase2._page')
@php $pageTitle = 'Depreciation Preview'; $pageSub = $year.'-'.str_pad($month,2,'0',STR_PAD_LEFT); @endphp
@section('page_body')
<form class="row g-2 mb-3" method="get" action="{{ url('accounting/depreciation/preview') }}">
    <div class="col-auto"><input type="number" name="year" value="{{ $year }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><input type="number" name="month" value="{{ $month }}" class="form-control form-control-sm" min="1" max="12"></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Refresh</button></div>
</form>
<p class="mb-3">Scheduled amount to post: <strong>LKR {{ number_format($total,2) }}</strong> across {{ count($lines) }} asset(s).</p>
<table class="table table-sm">
    <thead><tr><th>Asset</th><th class="text-end">Amount</th></tr></thead>
    <tbody>
    @forelse($lines as $row)
        <tr><td>{{ $row['asset']->asset_code }} — {{ $row['asset']->name }}</td><td class="text-end">{{ number_format($row['amount'],2) }}</td></tr>
    @empty
        <tr><td colspan="2" class="text-muted">No scheduled lines for this period.</td></tr>
    @endforelse
    </tbody>
</table>
<form method="post" action="{{ url('accounting/depreciation/runs') }}" class="mt-3">@csrf
    <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
    <input type="hidden" name="run_date" value="{{ $runDate }}">
    <button class="btn btn-primary btn-sm" @disabled($total<=0)>Post this period</button>
</form>
@endsection