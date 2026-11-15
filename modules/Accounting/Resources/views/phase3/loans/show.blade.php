@extends('accounting::phase2._page')
@php $pageTitle = $loan->loan_number; $pageSub = $loan->lender_name.' · '.$loan->status; @endphp
@section('page_actions')
    <a href="{{ url('accounting/loans/'.$loan->id.'/statement') }}" class="btn btn-outline-secondary btn-sm">Statement</a>
    <a href="{{ url('accounting/loans/'.$loan->id.'/payment') }}" class="btn btn-primary btn-sm">Record payment</a>
@endsection
@section('page_body')
<div class="row g-2 mb-3">
    <div class="col-md-3">Outstanding: <strong>LKR {{ number_format((float)$loan->outstanding_principal,2) }}</strong></div>
    <div class="col-md-3">Rate: {{ $loan->annual_interest_rate }}%</div>
</div>
@if($loan->status==='draft')
<form method="post" action="{{ url('accounting/loans/'.$loan->id.'/disburse') }}" class="d-flex gap-2 mb-3">@csrf
    <input type="date" name="disbursement_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
    <button class="btn btn-sm btn-success">Disburse</button>
</form>
@endif
<form method="post" action="{{ url('accounting/loans/'.$loan->id.'/schedule') }}" class="d-flex gap-2 mb-4">@csrf
    <input type="date" name="first_due_date" class="form-control form-control-sm" placeholder="First due">
    <button class="btn btn-sm btn-outline-primary">Rebuild schedule</button>
</form>
<h2 class="h6">Amortization schedule</h2>
<table class="table table-sm"><thead><tr><th>#</th><th>Due</th><th class="text-end">Principal</th><th class="text-end">Interest</th><th class="text-end">Payment</th><th>Status</th></tr></thead>
<tbody>
@foreach($loan->scheduleLines as $line)
<tr><td>{{ $line->line_no }}</td><td>{{ $line->due_date }}</td>
<td class="text-end">{{ number_format((float)$line->principal_amount,2) }}</td>
<td class="text-end">{{ number_format((float)$line->interest_amount,2) }}</td>
<td class="text-end">{{ number_format((float)$line->total_payment,2) }}</td>
<td>{{ $line->status }}</td></tr>
@endforeach
</tbody></table>
@endsection