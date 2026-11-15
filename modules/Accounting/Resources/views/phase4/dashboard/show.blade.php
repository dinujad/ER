@extends('accounting::phase2._page')
@section('page_body')
<div class="row g-3">
    <div class="col-md-4 acct-card p-3">Revenue: LKR {{ number_format($dashboard['revenue'] ?? 0, 2) }}</div>
    <div class="col-md-4">Gross profit: LKR {{ number_format($dashboard['gross_profit'] ?? 0, 2) }}</div>
    <div class="col-md-4">Working capital: LKR {{ number_format($dashboard['working_capital'] ?? 0, 2) }}</div>
</div>
@endsection