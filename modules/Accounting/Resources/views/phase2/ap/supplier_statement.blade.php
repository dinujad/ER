@extends('accounting::phase2._page')
@php $pageTitle = 'Supplier Statement PDF'; $pageSub = 'LKR'; @endphp
@section('page_body')
<form method="get" action="{{ url('accounting/payables/supplier-statement/pdf') }}" class="row g-2">
    <div class="col-md-3"><label class="form-label">Supplier ID</label><input type="number" name="supplier_id" class="form-control" value="{{ $supplierId }}" required></div>
    <div class="col-md-3"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="{{ $from }}" required></div>
    <div class="col-md-3"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="{{ $to }}" required></div>
    <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">Download PDF</button></div>
</form>
@endsection
