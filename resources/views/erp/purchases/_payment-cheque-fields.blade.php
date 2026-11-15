@php
    $pi = $pi ?? 0;
    $pay = $pay ?? [];
    $method = old('payments.'.$pi.'.payment_method', $pay['payment_method'] ?? '');
    $showCheque = $method === 'cheque';
    $showRef = $method === 'bank_transfer';
    $chequeDateVal = old('payments.'.$pi.'.cheque_date', $pay['cheque_date'] ?? '');
    if ($chequeDateVal instanceof \DateTimeInterface) {
        $chequeDateVal = $chequeDateVal->format('Y-m-d');
    } elseif (is_string($chequeDateVal) && $chequeDateVal !== '') {
        try { $chequeDateVal = \Illuminate\Support\Carbon::parse($chequeDateVal)->format('Y-m-d'); } catch (\Throwable $e) { $chequeDateVal = ''; }
    }
@endphp
<div class="payment-bank-ref mt-1 {{ $showRef ? '' : 'd-none' }}">
    <label class="form-label small mb-0">Transfer reference</label>
    <input type="text" name="payments[{{ $pi }}][reference]" class="form-control form-control-sm payment-reference" value="{{ old('payments.'.$pi.'.reference', $pay['reference'] ?? '') }}" placeholder="Transaction / transfer ref">
</div>
<div class="payment-cheque-fields mt-2 border-top pt-2 {{ $showCheque ? '' : 'd-none' }}">
    <div class="small fw-semibold text-muted mb-1">Cheque details</div>
    <div class="row g-2">
        <div class="col-12">
            <label class="form-label small mb-0">Cheque number <span class="text-danger">*</span></label>
            <input type="text" name="payments[{{ $pi }}][cheque_number]" class="form-control form-control-sm payment-cheque-number" value="{{ old('payments.'.$pi.'.cheque_number', $pay['cheque_number'] ?? '') }}" placeholder="e.g. 000123">
        </div>
        <div class="col-6">
            <label class="form-label small mb-0">Cheque date <span class="text-danger">*</span></label>
            <input type="date" name="payments[{{ $pi }}][cheque_date]" class="form-control form-control-sm payment-cheque-date" value="{{ $chequeDateVal }}">
        </div>
        <div class="col-6">
            <label class="form-label small mb-0">Issued bank <span class="text-danger">*</span></label>
            <input type="text" name="payments[{{ $pi }}][cheque_bank]" class="form-control form-control-sm payment-cheque-bank" value="{{ old('payments.'.$pi.'.cheque_bank', $pay['cheque_bank'] ?? '') }}" placeholder="Bank name">
        </div>
    </div>
</div>
