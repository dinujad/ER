{{-- expects: $order, $formAction, optional $hiddenFields, optional $retailMode --}}
@php
    $retailMode = $retailMode ?? false;
    $paid = (float) $order->paid_amount;
    $returned = (float) $order->total_returned;
    $net = (float) $order->net_total;
    $refunded = (float) $order->total_refunded;
    $refundable = (float) $order->refundable_to_customer;
    $order->loadMissing(['items.product', 'items.variation', 'payments']);
@endphp
<form method="POST" action="{{ $formAction }}" id="sale-return-form-{{ $order->id }}" class="sale-return-form"
      data-refundable="{{ number_format($refundable, 2, '.', '') }}"
      data-paid="{{ number_format($paid, 2, '.', '') }}"
      data-already-refunded="{{ number_format($refunded, 2, '.', '') }}"
      data-retail="{{ $retailMode ? '1' : '0' }}"
      onsubmit='return confirm(@json($retailMode ? "Record retail return? Items go back to retail stock and refund payment will be posted." : "Record sale return? Stock will be restocked and any refund will be posted."));'>
    @csrf
    @isset($hiddenFields)
        @foreach($hiddenFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    @endisset
    @if($retailMode)
        <input type="hidden" name="auto_refund" value="1">
    @endif

    <div class="alert alert-info py-2 small mb-3">
        Invoice total <strong>Rs {{ number_format((float) $order->total_amount, 2) }}</strong>
        · Already returned <strong>Rs {{ number_format($returned, 2) }}</strong>
        · Net <strong>Rs {{ number_format($net, 2) }}</strong>
        · Paid <strong>Rs {{ number_format($paid, 2) }}</strong>
        · Refundable cash <strong class="text-success">Rs {{ number_format($refundable, 2) }}</strong>
        <br>
        @if($retailMode)
            Returned qty goes back to <strong>retail stock</strong>. Paid amount is posted as a <strong>refund payment</strong> (same as add payment, reversed). Credit / unpaid portion is reduced automatically.
        @else
            Return lines use <strong>effective unit price</strong> (invoice + cash/payment discounts). Claimable credit = return total × <strong>30%</strong> — next invoice max claim also 30%. Stock always restocks.
        @endif
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <label class="form-label">Return date <span class="text-danger">*</span></label>
            <input type="date" name="return_date" class="form-control" value="{{ old('return_date', date('Y-m-d')) }}" required>
        </div>
        <div class="col-md-8">
            <label class="form-label">Reason</label>
            <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" maxlength="2000" placeholder="Damaged / wrong size / customer request…">
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th class="text-end">Sold</th>
                    <th class="text-end">Returned</th>
                    <th class="text-end">Max</th>
                    <th class="text-end">List</th>
                    <th class="text-end">Effective</th>
                    <th style="width:120px">Return qty</th>
                    <th class="text-end">Line</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $idx => $item)
                    @php
                        $max = $item->returnableQuantity();
                        $list = (float) $item->unit_price;
                        $effective = $item->effectiveReturnUnitPrice();
                    @endphp
                    <tr class="sale-return-line" data-price="{{ number_format($effective, 2, '.', '') }}" @if($max <= 0) style="opacity:.55" @endif>
                        <td>
                            {{ $item->product?->name ?? 'Product' }}
                            @if($item->variation)
                                <small class="d-block text-muted">({{ $item->variation->variation_name }})</small>
                            @endif
                            @if($item->product?->sku)
                                <small class="d-block text-muted">SKU {{ $item->product->sku }}</small>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $item->quantity, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $item->quantity_returned, 2) }}</td>
                        <td class="text-end">{{ number_format($max, 2) }}</td>
                        <td class="text-end text-muted">{{ number_format($list, 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($effective, 2) }}</td>
                        <td>
                            <input type="hidden" name="items[{{ $idx }}][order_item_id]" value="{{ $item->id }}">
                            <input type="number"
                                   name="items[{{ $idx }}][quantity]"
                                   class="form-control form-control-sm sale-return-qty"
                                   min="0"
                                   max="{{ number_format($max, 2, '.', '') }}"
                                   step="0.01"
                                   value="0"
                                   @disabled($max <= 0)
                                   @if($max <= 0) readonly @endif>
                        </td>
                        <td class="text-end font-monospace sale-return-line-total">0.00</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="7" class="text-end">Return total (effective)</th>
                    <th class="text-end font-monospace" id="sale-return-total-{{ $order->id }}">0.00</th>
                </tr>
                @unless($retailMode)
                <tr>
                    <th colspan="7" class="text-end text-muted">Claimable credit (30%)</th>
                    <th class="text-end font-monospace text-success" id="sale-return-claimable-{{ $order->id }}">0.00</th>
                </tr>
                @endunless
            </tfoot>
        </table>
    </div>

    <div class="border rounded p-3 bg-light">
        <div class="fw-semibold mb-2">{{ $retailMode ? 'Refund payment (add back to customer)' : 'Customer refund (optional)' }}</div>
        <p class="small text-muted mb-2">
            @if($retailMode)
                Refund is filled from the return total (cash already paid). Change the amount only if you are not refunding the full paid portion.
            @else
                Credit / unpaid portion is reduced automatically. Enter a refund only for money already paid that should go back to the customer.
                Max refundable after this return: up to paid − new net − already refunded.
            @endif
        </p>
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Refund amount</label>
                <input type="number" step="0.01" min="0" name="refund_amount" class="form-control sale-return-refund-amount" value="{{ old('refund_amount', $retailMode ? $refundable : 0) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Method</label>
                <select name="refund_method" class="form-select">
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="bank_transfer">Bank transfer</option>
                    <option value="cheque">Cheque</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Reference</label>
                <input type="text" name="refund_reference" class="form-control" value="{{ old('refund_reference') }}" placeholder="Optional">
            </div>
            <div class="col-md-3">
                <label class="form-label">Refund notes</label>
                <input type="text" name="refund_notes" class="form-control" value="{{ old('refund_notes') }}" placeholder="Optional">
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    const form = document.getElementById(@json('sale-return-form-' . $order->id));
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';
    const totalEl = document.getElementById(@json('sale-return-total-' . $order->id));
    const claimableEl = document.getElementById(@json('sale-return-claimable-' . $order->id));

    const refundInput = form.querySelector('.sale-return-refund-amount');
    const isRetail = form.dataset.retail === '1';
    const alreadyRefundable = parseFloat(form.dataset.refundable || '0') || 0;

    function recalc() {
        let total = 0;
        form.querySelectorAll('.sale-return-line').forEach(function (row) {
            const price = parseFloat(row.dataset.price || '0') || 0;
            const qtyInput = row.querySelector('.sale-return-qty');
            const qty = qtyInput && !qtyInput.disabled ? (parseFloat(qtyInput.value) || 0) : 0;
            const line = Math.round(price * qty * 100) / 100;
            const cell = row.querySelector('.sale-return-line-total');
            if (cell) cell.textContent = line.toFixed(2);
            total += line;
        });
        if (totalEl) totalEl.textContent = total.toFixed(2);
        if (claimableEl) claimableEl.textContent = (Math.round(total * 0.30 * 100) / 100).toFixed(2);
        if (isRetail && refundInput && !refundInput.dataset.manual) {
            const paidAmt = parseFloat(form.dataset.paid || '0') || 0;
            const alreadyRefundedAmt = parseFloat(form.dataset.alreadyRefunded || '0') || 0;
            const cap = Math.max(0, Math.round((paidAmt - alreadyRefundedAmt) * 100) / 100);
            const suggested = Math.min(cap, Math.round((alreadyRefundable + total) * 100) / 100);
            refundInput.value = suggested.toFixed(2);
        }
    }
    if (isRetail && refundInput) {
        refundInput.addEventListener('input', function () {
            refundInput.dataset.manual = '1';
        });
    }

    form.querySelectorAll('.sale-return-qty').forEach(function (input) {
        input.addEventListener('input', recalc);
        input.addEventListener('change', recalc);
    });
    recalc();
})();
</script>
