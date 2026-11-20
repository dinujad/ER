@extends('accounting::phase2._page')

@php $pageTitle = 'Pay Bills'; $pageSub = 'Unpaid bills due on or before ' . $dueBy . ' — tick bills to put them in a payment run'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/phase7/payment-runs') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-collection"></i> Payment runs</a>
    <a href="{{ url('accounting/phase6/bills/payment-run?due_by=' . $dueBy . '&export=csv') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv"></i> List as CSV</a>
@endsection

@section('page_body')
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3"><label class="form-label small mb-0">Due on or before</label><input type="date" name="due_by" value="{{ $dueBy }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Show</button></div>
    @if(config('dermas.require_bill_approval'))<div class="col-auto small text-muted">Only bills approved for payment are listed.</div>@endif
</form>

<form method="POST" action="{{ url('accounting/phase7/payment-runs') }}" id="runForm">
    @csrf
    <div class="card mb-3"><div class="card-body row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-0">Pay from</label>
            <select name="bank_account_id" class="form-select form-select-sm">
                @foreach($bankAccounts as $a)<option value="{{ $a->id }}">{{ $a->gl_code }} — {{ $a->name }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label class="form-label small mb-0">Payment date</label><input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required></div>
        <div class="col-md-4"><label class="form-label small mb-0">Notes</label><input name="notes" class="form-control form-control-sm" maxlength="255"></div>
        <div class="col-md-3 text-end">
            <div class="small">Selected <strong id="runCount">0</strong> bill(s) · Rs <strong id="runTotal">0.00</strong></div>
            <button class="btn btn-success btn-sm mt-1" id="runBtn" disabled><i class="bi bi-plus-circle"></i> Create payment run</button>
        </div>
    </div></div>

    <div class="mb-2"><strong>{{ $bills->count() }}</strong> bill(s) to {{ $bySupplier->count() }} supplier(s) · total <strong>Rs {{ number_format((float) $bills->sum(fn ($b) => (float) $b->balance), 2) }}</strong>
        <button type="button" class="btn btn-outline-secondary btn-xs ms-2" onclick="document.querySelectorAll('.bill-sel:not(:disabled)').forEach(c => c.checked = true); window.runUpd();"><i class="bi bi-check2-all"></i> Select all</button>
        <button type="button" class="btn btn-outline-secondary btn-xs" onclick="document.querySelectorAll('.bill-sel').forEach(c => c.checked = false); window.runUpd();">Clear</button></div>

    @forelse($bySupplier as $supplierBills)
        @php $s = $supplierBills->first()->supplier; @endphp
        <div class="card mb-2"><div class="card-header d-flex justify-content-between">
            <strong>{{ $s?->company_name ?: $s?->name }}</strong>
            <span class="small">@if(!$s?->bank_account_no)<span class="text-danger">no bank account on supplier</span> · @endif Rs {{ number_format((float) $supplierBills->sum(fn ($b) => (float) $b->balance), 2) }}</span>
        </div><div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light"><tr><th style="width:32px"></th><th>Bill</th><th>Supplier ref.</th><th>Bill date</th><th>Due</th><th class="text-end">Days late</th><th class="text-end">Balance</th><th class="text-end" style="width:140px">Pay now</th></tr></thead>
                <tbody>
                @foreach($supplierBills as $b)
                    @php $run = $inRuns[$b->id] ?? null; @endphp
                    <tr class="{{ $run ? 'text-muted' : '' }}">
                        <td><input type="checkbox" class="form-check-input bill-sel" name="purchase_ids[]" value="{{ $b->id }}" @disabled($run)></td>
                        <td><a href="{{ url('accounting/phase6/bills/' . $b->id) }}">{{ $b->purchase_no }}</a>@if($run) <span class="badge bg-secondary">in {{ $run }}</span>@endif</td>
                        <td class="small">{{ $b->reference_no }}</td>
                        <td>{{ $b->date?->format('Y-m-d') }}</td>
                        <td class="{{ $b->daysOverdue() > 0 ? 'text-danger' : '' }}">{{ $b->dueDate()?->format('Y-m-d') }}</td>
                        <td class="text-end">{{ $b->daysOverdue() ?: '' }}</td>
                        <td class="text-end font-monospace">{{ number_format((float) $b->balance, 2) }}</td>
                        <td><input type="number" step="0.01" min="0" max="{{ number_format((float) $b->balance, 2, '.', '') }}" name="amounts[{{ $b->id }}]" class="form-control form-control-sm text-end bill-amt" value="{{ number_format((float) $b->balance, 2, '.', '') }}" @disabled($run)></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div>
    @empty
        <div class="text-success">No bills due in this window.</div>
    @endforelse
</form>
<script>
(function () {
    window.runUpd = function () {
        var n = 0, t = 0;
        document.querySelectorAll('#runForm tbody tr').forEach(function (tr) {
            var c = tr.querySelector('.bill-sel'), a = tr.querySelector('.bill-amt');
            if (c && c.checked) { n++; t += parseFloat(a.value) || 0; }
        });
        document.getElementById('runCount').textContent = n;
        document.getElementById('runTotal').textContent = t.toFixed(2);
        document.getElementById('runBtn').disabled = n === 0;
    };
    document.getElementById('runForm').addEventListener('input', window.runUpd);
    document.getElementById('runForm').addEventListener('change', window.runUpd);
    window.runUpd();
})();
</script>
@endsection
