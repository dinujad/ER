@extends('accounting::phase2._page')

@php $pageTitle = 'New credit note — Invoice ' . $order->invoice_number; $pageSub = ($order->customer?->business_name ?: $order->customer?->name) . ' · price reduction or goodwill credit (no goods returned)'; @endphp

@section('page_body')
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ url('accounting/phase6/credit-notes') }}">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                <div class="mb-3">
                    <label class="form-label">Credit amount — at <strong>item price</strong> (same as the invoice lines, before any SSCL / VAT added on top)</label>
                    <input type="number" step="0.01" min="0.01" name="goods_amount" id="cnGoods" class="form-control" value="{{ old('goods_amount') }}" required>
                    <div class="form-text">SSCL and VAT are credited automatically in the same proportion as the invoice. Use a <strong>sale return</strong> instead when goods physically come back (stock is then restocked).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason (printed on the credit note)</label>
                    <input type="text" name="reason" class="form-control" maxlength="255" value="{{ old('reason') }}" required placeholder="e.g. Price agreed after quality complaint">
                </div>
                <div class="mb-3">
                    <label class="form-label">Credit note date</label>
                    <input type="date" name="credit_note_date" class="form-control" value="{{ old('credit_note_date', now()->toDateString()) }}">
                </div>
                <div class="alert alert-light border small" id="cnPreview">Enter an amount to preview the credit.</div>
                <button class="btn btn-warning">Issue credit note</button>
                <a href="{{ url('accounting/phase6/invoices/' . $order->id) }}" class="btn btn-outline-secondary">Cancel</a>
            </form>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body small">
            <div class="d-flex justify-content-between"><span>Invoice total</span><strong>{{ number_format($order->invoice_total, 2) }}</strong></div>
            <div class="d-flex justify-content-between"><span>Already returned / credited</span><span>{{ number_format($order->invoice_total - $creditable, 2) }}</span></div>
            <div class="d-flex justify-content-between"><span>Can still be credited (incl. tax)</span><strong>{{ number_format($creditable, 2) }}</strong></div>
            <div class="d-flex justify-content-between"><span>Balance due now</span><span>{{ number_format((float) $order->due_amount, 2) }}</span></div>
            <hr>
            <div class="text-muted">Posting: Dr Sales returns (goods) · Dr SSCL payable · Dr VAT output · Cr Accounts receivable. The balance due goes down by the total. If the customer had already paid, the credit becomes refundable.</div>
        </div></div>
    </div>
</div>
<script>
(function () {
    var ratio = {{ json_encode(round($ratio, 8)) }};
    var input = document.getElementById('cnGoods');
    var out = document.getElementById('cnPreview');
    function upd() {
        var g = parseFloat(input.value) || 0;
        if (g <= 0) { out.textContent = 'Enter an amount to preview the credit.'; return; }
        var total = Math.round(g * ratio * 100) / 100;
        out.innerHTML = 'Goods <strong>' + g.toFixed(2) + '</strong> + SSCL &amp; VAT <strong>' + (total - g).toFixed(2) + '</strong> = total credit <strong>' + total.toFixed(2) + '</strong>';
    }
    input.addEventListener('input', upd); upd();
})();
</script>
@endsection
