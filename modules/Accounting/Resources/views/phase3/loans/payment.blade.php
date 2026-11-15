@extends('accounting::phase2._page')
@php $pageTitle = 'Loan payment'; $pageSub = $loan->loan_number; @endphp
@section('page_body')
<form method="post" action="{{ url('accounting/loans/'.$loan->id.'/payment') }}" class="row g-3">
    @csrf
    <div class="col-md-3"><label class="form-label">Payment date</label><input type="date" name="payment_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><label class="form-label">Principal</label><input type="number" step="0.01" name="principal_paid" value="{{ optional($nextLine)->principal_amount ?? 0 }}" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><label class="form-label">Interest</label><input type="number" step="0.01" name="interest_paid" value="{{ optional($nextLine)->interest_amount ?? 0 }}" class="form-control form-control-sm" required></div>
    @if($nextLine)
    <input type="hidden" name="schedule_line_id" value="{{ $nextLine->id }}">
    @endif
    <div class="col-12"><button class="btn btn-primary btn-sm">Post payment</button></div>
</form>
@endsection