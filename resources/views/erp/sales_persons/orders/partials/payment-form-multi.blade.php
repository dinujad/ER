@php
    $paymentRows = old('payments', [['payment_method' => 'cash', 'settlement_amount' => '', 'discount_type' => 'fixed', 'discount_value' => '']]);
    if (!is_array($paymentRows) || $paymentRows === []) {
        $paymentRows = [['payment_method' => 'cash', 'settlement_amount' => '', 'discount_type' => 'fixed', 'discount_value' => '']];
    }
@endphp
<form id="multi-payment-form" action="{{ $formAction ?? route('sales-person.orders.payment.store', $order->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @error('payments')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror
    <div class="mb-3">
        <label class="form-label">Payment Date</label>
        <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
        @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Payment lines</label>
        <button type="button" class="btn btn-sm btn-outline-success" id="add-payment-row">+ Add row</button>
    </div>
    <div id="payment-lines" class="mb-2" data-due="{{ $order->due_amount }}">
        @foreach($paymentRows as $rowIndex => $row)
            @include('erp.sales_persons.orders.partials.payment-line', [
                'index' => $rowIndex,
                'row' => $row,
                'chequeMin' => $chequeMin,
                'chequeMaxNormal' => $chequeMaxNormal,
                'chequeMaxException' => $chequeMaxException,
                'chequeMinException' => $chequeMinException,
                'canRemove' => count($paymentRows) > 1,
            ])
        @endforeach
    </div>
    <div class="mb-3">
        <label class="form-label" for="transport_amount">Transport (Rs) <span class="text-muted small">(optional)</span></label>
        <input type="number" step="0.01" min="0" name="transport_amount" id="transport_amount" class="form-control @error('transport_amount') is-invalid @enderror" value="{{ old('transport_amount') }}" placeholder="0.00">
        @error('transport_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <div class="form-text">ගානක් දැම්මොත් විතරක් වෙනම Transport payment එකක් add වෙලා payment ගණනට එකතු වෙනවා.</div>
    </div>
    <div class="alert alert-light border py-2 mb-3 small">
        <div class="d-flex justify-content-between">
            <span>Total settlement (due cleared):</span>
            <strong id="payment-settlement-total">0.00</strong>
        </div>
        <div class="d-flex justify-content-between">
            <span>Total received (cash):</span>
            <strong id="payment-entering-total">0.00</strong>
        </div>
        <div class="d-none justify-content-between" id="payment-transport-row">
            <span>Transport:</span>
            <strong id="payment-transport-total">0.00</strong>
        </div>
        <div class="d-flex justify-content-between text-muted">
            <span>Due amount:</span>
            <span>{{ number_format($order->due_amount, 2) }}</span>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Reference Number <span class="text-muted small">(optional)</span></label>
        <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Notes <span class="text-muted small">(optional)</span></label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
    </div>
    @php
        $claimService = app(\App\Services\SaleReturnClaimService::class);
        $claimBreakdown = $order->customer_id
            ? $claimService->breakdown($order->customer ?? $order->customer()->first())
            : ['previous' => 0, 'system' => 0, 'full' => 0];
        $maxClaimOnInvoice = $claimService->maxClaimForInvoiceTotal((float) $order->total_amount);
        $claimRoom = max(0, round($maxClaimOnInvoice - (float) ($order->claim_credit_amount ?? 0), 2));
        $maxClaimNow = min($claimBreakdown['full'], $claimRoom, (float) $order->due_amount);
    @endphp
    @if($maxClaimNow > 0)
        <div class="border rounded p-2 mb-3 bg-white">
            <div class="form-check">
                <input type="checkbox" name="claim_return" value="1" id="claim_return" class="form-check-input" @checked(old('claim_return'))>
                <label class="form-check-label" for="claim_return">මේ return credit එකෙන් claim කරනවා</label>
            </div>
            <div id="claim_return_fields" class="mt-2 {{ old('claim_return') ? '' : 'd-none' }}">
                <label class="form-label small mb-1" for="claim_amount">Claim amount (Rs)</label>
                <input type="number" step="0.01" min="0.01" max="{{ number_format($maxClaimNow, 2, '.', '') }}"
                       name="claim_amount" id="claim_amount" class="form-control form-control-sm @error('claim_amount') is-invalid @enderror"
                       value="{{ old('claim_amount') }}">
                @error('claim_amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="small text-muted mt-2">
                පරණ return Rs {{ number_format($claimBreakdown['previous'], 2) }}
                · system return Rs {{ number_format($claimBreakdown['system'], 2) }}
                · මුළු ඉතුරු Rs {{ number_format($claimBreakdown['full'], 2) }}.
                මේ order එකට දැන් claim කරන්න පුළුවන් උපරිම ගාන <strong>Rs {{ number_format($maxClaimNow, 2) }}</strong>
                (invoice එකේ 30% සහ ඉතිරි due එක).
            </div>
        </div>
    @endif
    <button type="submit" class="btn btn-success w-100" id="save-payments-btn">Save payments</button>
</form>
<template id="payment-line-template">
    @include('erp.sales_persons.orders.partials.payment-line', [
        'index' => '__INDEX__',
        'row' => ['payment_method' => 'cash', 'settlement_amount' => '', 'discount_type' => 'fixed', 'discount_value' => ''],
        'chequeMin' => $chequeMin,
        'chequeMaxNormal' => $chequeMaxNormal,
        'chequeMaxException' => $chequeMaxException,
        'chequeMinException' => $chequeMinException,
        'canRemove' => true,
    ])
</template>
