@php
    $idx = $index;
    $method = $row['payment_method'] ?? 'cash';
    $settlement = $row['settlement_amount'] ?? ($row['amount'] ?? '');
    $discountType = $row['discount_type'] ?? 'fixed';
    $discountValue = $row['discount_value'] ?? '';
    $chequeDateVal = $row['cheque_date'] ?? '';
    $exceptionRequested = !empty($row['cheque_exception_requested']);
    $exceptionReason = $row['cheque_exception_reason'] ?? '';
@endphp
<div class="payment-line-row border rounded p-2 mb-2 bg-light" data-index="{{ $idx }}">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="small fw-semibold text-muted">Payment line</span>
        @if($canRemove)
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 remove-payment-row" title="Remove row">&times;</button>
        @endif
    </div>
    <div class="mb-2">
        <label class="form-label small mb-1">Payment method</label>
        <select name="payments[{{ $idx }}][payment_method]" class="form-select form-select-sm payment-method-select @error("payments.{$idx}.payment_method") is-invalid @enderror">
            <option value="cash" @selected($method === 'cash')>Cash</option>
            <option value="bank_transfer" @selected($method === 'bank_transfer')>Bank Transfer</option>
            <option value="cheque" @selected($method === 'cheque')>Cheque</option>
            <option value="card" @selected($method === 'card')>Card</option>
        </select>
        @error("payments.{$idx}.payment_method")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="payment-discount-block border rounded p-2 mb-2 bg-white">
        <div class="small fw-semibold text-muted mb-2">Method discount</div>
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label small mb-1 payment-base-label">Cash given (Rs)</label>
                <input type="number" step="0.01" min="0.01" name="payments[{{ $idx }}][settlement_amount]" value="{{ $settlement }}"
                       class="form-control form-control-sm payment-settlement-input @error("payments.{$idx}.settlement_amount") is-invalid @enderror @error("payments.{$idx}.amount") is-invalid @enderror"
                       placeholder="0.00">
                @error("payments.{$idx}.settlement_amount")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error("payments.{$idx}.amount")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Discount type</label>
                <select name="payments[{{ $idx }}][discount_type]" class="form-select form-select-sm payment-discount-type">
                    <option value="fixed" @selected($discountType === 'fixed')>Fixed (Rs)</option>
                    <option value="percentage" @selected($discountType === 'percentage')>Percentage (%)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Discount value</label>
                <input type="number" step="0.01" min="0" name="payments[{{ $idx }}][discount_value]" value="{{ $discountValue }}"
                       class="form-control form-control-sm payment-discount-value" placeholder="0">
            </div>
            <div class="col-md-6">
                <label class="form-label small mb-1">Discount (Rs)</label>
                <input type="text" class="form-control form-control-sm payment-discount-amount" readonly value="0.00">
            </div>
            <div class="col-12">
                <label class="form-label small mb-1">Amount received (Rs)</label>
                <input type="text" class="form-control form-control-sm payment-amount-display fw-semibold" readonly value="0.00">
                <div class="small text-success mt-1 payment-next-hint d-none"></div>
            </div>
        </div>
    </div>

    <div class="cheque-fields border-top pt-2 mt-2" style="display: {{ $method === 'cheque' ? 'block' : 'none' }};">
        <p class="small text-secondary mb-2 mb-0">Cheque date must be {{ $chequeMin }} – {{ $chequeMaxNormal }} (45 days), unless you request an exception.</p>
        <div class="mb-2">
            <label class="form-label small mb-1" for="payments_{{ $idx }}_cheque_date">Cheque date <span class="text-danger">*</span></label>
            <input type="date" id="payments_{{ $idx }}_cheque_date" name="payments[{{ $idx }}][cheque_date]" value="{{ $chequeDateVal }}" class="form-control form-control-sm cheque-date-input @error("payments.{$idx}.cheque_date") is-invalid @enderror">
            @error("payments.{$idx}.cheque_date")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="mb-2">
            <label class="form-label small mb-1" for="payments_{{ $idx }}_cheque_image">Cheque photo <span class="text-muted">(optional)</span></label>
            <input type="file" id="payments_{{ $idx }}_cheque_image" name="payments[{{ $idx }}][cheque_image]" class="form-control form-control-sm cheque-image-input @error("payments.{$idx}.cheque_image") is-invalid @enderror" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
            @error("payments.{$idx}.cheque_image")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="form-check mb-2">
            <input type="checkbox" name="payments[{{ $idx }}][cheque_exception_requested]" value="1" id="payments_{{ $idx }}_cheque_exception" class="form-check-input cheque-exception-input" @checked($exceptionRequested)>
            <label class="form-check-label small" for="payments_{{ $idx }}_cheque_exception">Request exception (date outside 45 days)</label>
        </div>
        <div>
            <label class="form-label small mb-1" for="payments_{{ $idx }}_cheque_exception_reason">Exception reason (English, min. 30 chars)</label>
            <textarea id="payments_{{ $idx }}_cheque_exception_reason" name="payments[{{ $idx }}][cheque_exception_reason]" class="form-control form-control-sm @error("payments.{$idx}.cheque_exception_reason") is-invalid @enderror" rows="2">{{ $exceptionReason }}</textarea>
            @error("payments.{$idx}.cheque_exception_reason")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>
</div>
