@extends('accounting::phase2._page')
@php $pageTitle = 'Production costing'; $pageSub = 'WIP · FG · overhead'; @endphp
@section('page_body')
<form class="row g-2 mb-4" method="get">
    <div class="col-auto"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Update period</button></div>
</form>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-body py-3"><div class="small text-muted">WIP (GL 1320)</div><div class="h5 mb-0">LKR {{ number_format($wip,2) }}</div></div></div>
    <div class="col-md-3"><div class="card card-body py-3"><div class="small text-muted">Finished goods (1330)</div><div class="h5 mb-0">LKR {{ number_format($fg,2) }}</div></div></div>
    <div class="col-md-3"><div class="card card-body py-3"><div class="small text-muted">Raw materials (1310)</div><div class="h5 mb-0">LKR {{ number_format($raw,2) }}</div></div></div>
    <div class="col-md-3"><div class="card card-body py-3"><div class="small text-muted">Open jobs</div><div class="h5 mb-0">{{ $jobCount }}</div></div></div>
</div>
<h2 class="h6">Overhead allocation (simple average)</h2>
<ul class="mb-0">
    <li>Period expenses: LKR {{ number_format($overhead['total_expenses'],2) }}</li>
    <li>Finished pieces: {{ $overhead['finished_pieces'] }} ({{ $overhead['pieces_source'] }})</li>
    <li>Overhead per piece: LKR {{ number_format($overhead['overhead_per_piece'],4) }}</li>
</ul>
@endsection