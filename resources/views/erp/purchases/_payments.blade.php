@php
    $paymentRows = $paymentRows ?? old('payments', [['amount' => '', 'payment_method' => '', 'reference' => '', 'cheque_number' => '', 'cheque_date' => '', 'cheque_bank' => '']]);
    if (! is_array($paymentRows) || count($paymentRows) === 0) {
        $paymentRows = [['amount' => '', 'payment_method' => '', 'reference' => '', 'cheque_number' => '', 'cheque_date' => '', 'cheque_bank' => '']];
    }
@endphp
<div class="mb-2">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <label class="form-label small mb-0">Payments</label>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-payment-row">+ Add Payment</button>
    </div>
    <div id="payments-list" class="d-flex flex-column gap-2">
        @foreach($paymentRows as $pi => $pay)
        <div class="payment-row border rounded p-2 bg-light">
            <div class="row g-2 align-items-end">
                <div class="col-5">
                    <label class="form-label small mb-0">Amount</label>
                    <input type="number" name="payments[{{ $pi }}][amount]" class="form-control form-control-sm payment-amount" step="0.01" min="0" value="{{ old('payments.'.$pi.'.amount', $pay['amount'] ?? '') }}" placeholder="0.00">
                </div>
                <div class="col-5">
                    <label class="form-label small mb-0">Method</label>
                    <select name="payments[{{ $pi }}][payment_method]" class="form-select form-select-sm payment-method">
                        <option value="">—</option>
                        <option value="cash" {{ old('payments.'.$pi.'.payment_method', $pay['payment_method'] ?? '') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="cheque" {{ old('payments.'.$pi.'.payment_method', $pay['payment_method'] ?? '') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="bank_transfer" {{ old('payments.'.$pi.'.payment_method', $pay['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    </select>
                </div>
                <div class="col-2 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-payment-row" title="Remove">&times;</button>
                </div>
            </div>
            @include('erp.purchases._payment-cheque-fields', ['pi' => $pi, 'pay' => $pay])
        </div>
        @endforeach
    </div>
    <div class="form-text small text-muted d-none" id="cash-type-hint">Full payment: payment lines must total the grand total.</div>
</div>
<div class="mb-2 d-flex justify-content-between"><span class="text-muted small">Total Paid</span><span id="sum-paid" class="small fw-semibold">0.00</span></div>
<div class="mb-2 d-flex justify-content-between"><span class="text-muted small">Balance</span><span id="sum-balance" class="small">0.00</span></div>
