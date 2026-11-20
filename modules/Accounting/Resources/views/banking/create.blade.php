@extends('accounting::phase2._page')

@php $pageTitle = 'Add Bank Account'; $pageSub = 'Account details → ledger account → opening balance → statement source'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/banking') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
@endsection

@section('page_body')
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ url('accounting/banking') }}" id="addBank">
@csrf
<div class="row g-3">
<div class="col-lg-8">

    <div class="card mb-3"><div class="card-header"><strong>1. Account details</strong></div><div class="card-body row g-2">
        <div class="col-md-6"><label class="form-label small mb-0">Bank</label><input name="bank_name" value="{{ old('bank_name') }}" class="form-control form-control-sm" placeholder="Commercial Bank" required></div>
        <div class="col-md-6"><label class="form-label small mb-0">Display name</label><input name="display_name" value="{{ old('display_name') }}" class="form-control form-control-sm" placeholder="Commercial Bank – Collections" required></div>
        <div class="col-md-4"><label class="form-label small mb-0">Account type</label>
            <select name="account_type" class="form-select form-select-sm" id="accType">
                @foreach(\App\Services\Accounting\BankAccountService::TYPES as $k => $label)<option value="{{ $k }}" @selected(old('account_type', 'current') === $k)>{{ $label }}</option>@endforeach
            </select></div>
        <div class="col-md-5"><label class="form-label small mb-0">Account number</label><input name="account_number" value="{{ old('account_number') }}" class="form-control form-control-sm" placeholder="Only the last 4 digits are shown in lists"></div>
        <div class="col-md-3"><label class="form-label small mb-0">Currency</label><select name="currency" class="form-select form-select-sm"><option value="LKR">LKR</option></select></div>
    </div></div>

    <div class="card mb-3"><div class="card-header"><strong>2. Ledger account</strong> <span class="small text-muted">— where this account's transactions are booked</span></div><div class="card-body">
        <div class="form-check"><input class="form-check-input" type="radio" name="ledger_mode" value="new" id="lmNew" @checked(old('ledger_mode', 'new') === 'new')>
            <label class="form-check-label" for="lmNew">Create a new ledger account <span class="small text-muted" id="newHint">(Asset — bank)</span></label></div>
        <div class="form-check mt-1"><input class="form-check-input" type="radio" name="ledger_mode" value="existing" id="lmOld" @checked(old('ledger_mode') === 'existing')>
            <label class="form-check-label" for="lmOld">This account is already in the chart of accounts</label></div>
        <select name="chart_of_account_id" class="form-select form-select-sm mt-2" id="ledgerSel">
            <option value="">— choose ledger account —</option>
            @foreach($ledgers as $l)
                <option value="{{ $l->id }}" data-balance="{{ $balances[$l->id] ?? 0 }}" @selected(old('chart_of_account_id') == $l->id)>{{ $l->gl_code }} — {{ $l->name }} ({{ $l->account_type }}) · balance {{ number_format($balances[$l->id] ?? 0, 2) }}</option>
            @endforeach
        </select>
        <div class="small text-muted mt-1" id="ledgerNote"></div>
    </div></div>

    <div class="card mb-3"><div class="card-header"><strong>3. Opening balance</strong></div><div class="card-body row g-2">
        <div class="col-md-5"><label class="form-label small mb-0">Balance on the day you start using DERMAS for this account</label><input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance') }}" class="form-control form-control-sm" id="opBal" placeholder="0.00"></div>
        <div class="col-md-4"><label class="form-label small mb-0">As at date</label><input type="date" name="opening_balance_date" value="{{ old('opening_balance_date', now()->toDateString()) }}" class="form-control form-control-sm"></div>
        <div class="col-12 small text-muted" id="opHelp">Bank: money in the account (negative = overdraft). Credit card: amount owed. Leave empty to start from zero. Posted against <em>3050 Opening Balance Equity</em>.</div>
    </div></div>

    <div class="card mb-3"><div class="card-header"><strong>4. Statement source</strong></div><div class="card-body">
        <div class="form-check"><input class="form-check-input" type="radio" checked disabled><label class="form-check-label">Manual import — upload CSV / XLSX statements from internet banking</label></div>
        <div class="form-check mt-1 text-muted"><input class="form-check-input" type="radio" disabled><label class="form-check-label">Connect bank feed — not available yet for Sri Lankan banks. When it is, it links to this same ledger account.</label></div>
    </div></div>

    <button class="btn btn-primary">Add bank account</button>
</div>
<div class="col-lg-4">
    <div class="card"><div class="card-body small text-muted">
        <div class="fw-semibold text-body mb-1">What happens next</div>
        <ol class="ps-3 mb-0">
            <li>Import the first statement (CSV / XLSX) and save the column layout for this bank.</li>
            <li>Match each bank line to a payment, or categorise new items such as bank charges.</li>
            <li>Reconcile: enter the statement ending balance and tick cleared items until the difference is 0.00.</li>
        </ol>
    </div></div>
</div>
</div>
</form>
<script>
(function () {
    var type = document.getElementById('accType'), sel = document.getElementById('ledgerSel'), note = document.getElementById('ledgerNote'), op = document.getElementById('opBal');
    function upd() {
        var existing = document.getElementById('lmOld').checked;
        sel.style.display = existing ? '' : 'none';
        document.getElementById('newHint').textContent = type.value === 'credit_card' ? '(Liability — credit card)' : '(Asset — bank)';
        note.textContent = '';
        if (existing && sel.value) {
            var b = parseFloat(sel.selectedOptions[0].dataset.balance || '0');
            if (Math.abs(b) > 0.005) { note.textContent = 'This ledger already has a balance of ' + b.toFixed(2) + ' — it is used as the opening balance; nothing extra is posted.'; op.value = ''; op.disabled = true; }
            else { op.disabled = false; }
        } else { op.disabled = false; }
    }
    document.querySelectorAll('input[name=ledger_mode]').forEach(function (r) { r.addEventListener('change', upd); });
    type.addEventListener('change', upd); sel.addEventListener('change', upd); upd();
})();
</script>
@endsection
