@extends('accounting::phase2._page')
@php $pageTitle = 'Bank Reconciliation PDF'; $pageSub = 'LKR'; @endphp
@section('page_body')
<form method="get" action="{{ url('accounting/bank-reconciliation-report/pdf') }}" class="row g-2">
    <div class="col-md-3"><label class="form-label">Bank GL Account ID</label><input type="number" name="chart_of_account_id" class="form-control" value="{{ $accountId }}" required></div>
    <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="{{ $from }}" required></div>
    <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="{{ $to }}" required></div>
    <div class="col-md-2"><label class="form-label">Statement ending</label><input type="number" step="0.01" name="ending_balance" class="form-control" value="{{ $ending }}"></div>
    <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">Download PDF</button></div>
</form>
@endsection
