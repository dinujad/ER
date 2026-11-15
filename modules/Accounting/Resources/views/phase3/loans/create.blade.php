@extends('accounting::phase2._page')
@php $pageTitle = 'Register Loan'; $pageSub = 'Draft liability'; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/loans') }}" class="row g-3">
    @csrf
    <div class="col-md-3"><label class="form-label">Loan number</label><input name="loan_number" value="{{ $suggestedNumber }}" class="form-control form-control-sm"></div>
    <div class="col-md-5"><label class="form-label">Lender</label><input name="lender_name" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><label class="form-label">Principal</label><input type="number" step="0.01" name="principal" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><label class="form-label">Rate %</label><input type="number" step="0.01" name="annual_interest_rate" value="12" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label">Term (months)</label><input type="number" name="term_months" value="12" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><label class="form-label">First due</label><input type="date" name="first_due_date" class="form-control form-control-sm"></div>
    <div class="col-12"><button class="btn btn-primary btn-sm">Save</button></div>
</form>
@endsection