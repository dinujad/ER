@php
    $subtotalVal = number_format((float) $subtotal, 2, '.', '');
    $discountType = old('discount_type', $order->discount_type ?? 'fixed');
    $discountValue = old('discount_value', $order->discount_value ?? 0);
    $transport = old('shipping', $order->shipping ?? 0);
    $otherExpenses = old('invoice_other_expenses', $order->invoice_other_expenses ?? 0);
    $claimService = app(\App\Services\SaleReturnClaimService::class);
    $availableClaim = $order->customer_id
        ? $claimService->availableCreditForCustomer($order->customer ?? $order->customer()->first())
        : 0;
    $previewNet = (float) ($order->total_amount ?? $subtotal);
    $maxClaimOnInvoice = $claimService->maxClaimForInvoiceTotal($previewNet);
    $maxClaimAllowed = min($availableClaim, $maxClaimOnInvoice);
    $showClaim = ($showClaimCredit ?? true) && ($order->customer_id || $availableClaim > 0);
@endphp
<div class="invoice-financial-block" data-subtotal="{{ $subtotalVal }}" data-available-claim="{{ number_format($availableClaim, 2, '.', '') }}" data-claim-rate="0.30">
    <div class="row g-2 align-items-end">
        <div class="col-md-3 col-6">
            <label class="form-label">Subtotal</label>
            <input type="text" class="form-control invoice-subtotal" value="{{ $subtotalVal }}" readonly>
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">Discount Type</label>
            <select name="discount_type" class="form-select invoice-discount-type">
                <option value="fixed" @selected($discountType === 'fixed')>Fixed (Rs)</option>
                <option value="percentage" @selected($discountType === 'percentage')>Percentage (%)</option>
            </select>
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">Discount Value</label>
            <input type="number" step="0.01" min="0" name="discount_value" class="form-control invoice-discount-value" value="{{ $discountValue }}">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">Discount (Rs)</label>
            <input type="text" class="form-control invoice-discount-amount" readonly value="0.00">
        </div>
        <div class="col-md-3 col-6">
            <label class="form-label">Transport (Rs)</label>
            <input type="number" step="0.01" min="0" name="shipping" class="form-control invoice-transport" value="{{ $transport }}">
        </div>
    </div>
    <div class="row g-2 mt-1 align-items-end">
        <div class="col-md-3 col-6">
            <label class="form-label">Other expenses (Rs)</label>
            <input type="number" step="0.01" min="0" name="invoice_other_expenses" class="form-control invoice-other-expenses" value="{{ $otherExpenses }}">
        </div>
        @if($showClaim)
        <div class="col-md-3 col-6">
            <label class="form-label">Return claim (Rs)</label>
            <input type="number" step="0.01" min="0" max="{{ number_format($maxClaimAllowed, 2, '.', '') }}"
                   name="claim_credit_amount" class="form-control invoice-claim-credit"
                   value="{{ old('claim_credit_amount', 0) }}"
                   @if($maxClaimAllowed <= 0) readonly @endif>
            <small class="text-muted">
                Available: <strong>Rs {{ number_format($availableClaim, 2) }}</strong>
                (previous return + system return)
                · Max this invoice (30%): <strong class="invoice-max-claim">Rs {{ number_format($maxClaimOnInvoice, 2) }}</strong>
            </small>
        </div>
        @endif
        <div class="col-md-{{ $showClaim ? '6' : '9' }} col-6 text-md-end mt-2 mt-md-0">
            <strong>Net Total: Rs <span class="invoice-net-total">{{ number_format($previewNet, 2) }}</span></strong>
            @if($showClaim)
                <div class="small text-muted">After claim due ↓ by claim amount (invoice total stays).</div>
            @endif
        </div>
    </div>
</div>
