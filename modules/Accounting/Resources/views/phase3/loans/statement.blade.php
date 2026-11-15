@extends('accounting::phase2._page')
@php $pageTitle = 'Loan statement'; $pageSub = $loan->loan_number; @endphp
@section('page_body')
<div class="row g-3 mb-4">
    <div class="col-md-3">Original principal: {{ number_format((float)$loan->principal,2) }}</div>
    <div class="col-md-3">Outstanding: {{ number_format((float)$loan->outstanding_principal,2) }}</div>
    <div class="col-md-3">Paid (total): {{ number_format((float)$paidTotal,2) }}</div>
    <div class="col-md-3">Interest paid: {{ number_format((float)$interestPaid,2) }}</div>
</div>
<h2 class="h6">Payments</h2>
<table class="table table-sm"><thead><tr><th>Date</th><th class="text-end">Principal</th><th class="text-end">Interest</th><th class="text-end">Total</th></tr></thead>
<tbody>
@forelse($loan->payments as $p)
<tr><td>{{ $p->payment_date }}</td>
<td class="text-end">{{ number_format((float)$p->principal_paid,2) }}</td>
<td class="text-end">{{ number_format((float)$p->interest_paid,2) }}</td>
<td class="text-end">{{ number_format((float)$p->total_paid,2) }}</td></tr>
@empty
<tr><td colspan="4" class="text-muted">No payments recorded.</td></tr>
@endforelse
</tbody></table>
@endsection