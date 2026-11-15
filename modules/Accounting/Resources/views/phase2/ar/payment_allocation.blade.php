@extends('accounting::phase2._page')

@php $pageTitle = 'Customer Payment Allocation'; $pageSub = 'AR · LKR'; @endphp

@section('page_body')
<form method="get" class="row g-2 mb-3">
    <div class="col-md-6">
        <select name="payment_id" class="form-select form-select-sm">
            <option value="">Select payment…</option>
            @foreach($payments as $p)
                <option value="{{ $p->id }}" @selected($paymentId==$p->id)>#{{ $p->id }} · {{ $p->payment_date }} · LKR {{ number_format((float)$p->amount,2) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Load</button></div>
</form>

@if(!empty($arReconcile))
<p class="small text-muted">AR control vs subledger (as of {{ $arReconcile['as_of'] }}): GL {{ $arReconcile['gl_ar'] }} · Subledger {{ $arReconcile['subledger_ar'] }} · Diff {{ $arReconcile['difference'] }}</p>
@endif
@if($payment)
<p>Payment #{{ $payment->id }} · Amount LKR {{ number_format((float)$payment->amount,2) }}
 · Allocated {{ number_format((float)$payment->amount - (float)($unallocated ?? 0), 2) }}
 · <strong>Unallocated / advance LKR {{ number_format((float)($unallocated ?? 0), 2) }}</strong>
</p>
@if($allocations->count())
<h2 class="h6">Existing allocations</h2>
<ul>@foreach($allocations as $a)<li>Order #{{ $a->order_id }} → {{ number_format((float)$a->amount,2) }}</li>@endforeach</ul>
@endif

<form method="post" action="{{ url('accounting/receivables/payment-allocation') }}">
    @csrf
    <input type="hidden" name="payment_id" value="{{ $payment->id }}">
    <table class="table table-sm">
        <thead><tr><th>Invoice</th><th>Total</th><th>Allocate</th></tr></thead>
        <tbody>
        @foreach($openInvoices as $i => $inv)
            <tr>
                <td>Order #{{ $inv->id }} <input type="hidden" name="allocations[{{ $i }}][order_id]" value="{{ $inv->id }}"></td>
                <td>{{ number_format((float)($inv->total_amount ?? 0),2) }}</td>
                <td><input type="number" step="0.01" min="0" name="allocations[{{ $i }}][amount]" class="form-control form-control-sm" value="0"></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <button class="btn btn-primary btn-sm">Save Allocations</button>
</form>
@endif
@endsection
