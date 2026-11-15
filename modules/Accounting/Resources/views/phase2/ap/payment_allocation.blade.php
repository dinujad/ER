@extends('accounting::phase2._page')

@php $pageTitle = 'Supplier Payment Allocation'; $pageSub = 'AP · LKR'; @endphp

@section('page_body')
<form method="get" class="row g-2 mb-3">
    <div class="col-md-6">
        <select name="purchase_payment_id" class="form-select form-select-sm">
            <option value="">Select payment…</option>
            @foreach($payments as $p)
                <option value="{{ $p->id }}" @selected($paymentId==$p->id)>#{{ $p->id }} · {{ $p->payment_date }} · LKR {{ number_format((float)$p->amount,2) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Load</button></div>
</form>

@if($payment)
<p>Payment #{{ $payment->id }} · Amount LKR {{ number_format((float)$payment->amount,2) }}</p>
<form method="post" action="{{ url('accounting/payables/payment-allocation') }}">
    @csrf
    <input type="hidden" name="purchase_payment_id" value="{{ $payment->id }}">
    <table class="table table-sm">
        <thead><tr><th>Bill</th><th>Total</th><th>Allocate</th></tr></thead>
        <tbody>
        @foreach($openBills as $i => $b)
            <tr>
                <td>Purchase #{{ $b->id }} <input type="hidden" name="allocations[{{ $i }}][purchase_id]" value="{{ $b->id }}"></td>
                <td>{{ number_format((float)($b->total ?? 0),2) }}</td>
                <td><input type="number" step="0.01" min="0" name="allocations[{{ $i }}][amount]" class="form-control form-control-sm" value="0"></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <button class="btn btn-primary btn-sm">Save Allocations</button>
</form>
@endif
@endsection
