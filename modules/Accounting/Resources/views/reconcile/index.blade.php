@extends('accounting::layouts.app')
@section('title', 'Reconcile')
@section('css')
@include('accounting::layouts._acct_theme')
<style>
.reconcile-panel{background:#fff;border:1px solid #e9ecef;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.05);margin-bottom:20px}
.reconcile-panel-head{background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e9ecef;padding:16px 22px;border-radius:12px 12px 0 0;display:flex;align-items:center;gap:12px}
.reconcile-panel-head .rp-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.reconcile-panel-head h6{font-weight:700;font-size:.95rem;color:#1e293b;margin:0}
.reconcile-panel-head small{color:#64748b;font-size:.78rem}
.reconcile-panel-body{padding:22px}
.info-row{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-radius:8px;background:#f8fafc;margin-bottom:10px;font-size:.9rem}
.info-row span{color:#64748b}
.info-row strong{color:#1e293b}
.btn-reconcile{background:linear-gradient(135deg,#1e3a5f,#0d2137);color:#fff;border:none;border-radius:9px;padding:10px 28px;font-weight:600;font-size:.95rem;cursor:pointer;transition:all .18s}
.btn-reconcile:hover{background:linear-gradient(135deg,#2563eb,#1e3a5f);transform:translateY(-1px)}
.btn-undo{background:#fef2f2;color:#dc2626;border:1.5px solid #fecaca;border-radius:9px;padding:10px 20px;font-weight:600;font-size:.9rem;cursor:pointer;transition:all .18s}
.btn-undo:hover{background:#fee2e2}
.btn-undo:disabled{opacity:.45;cursor:not-allowed}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<div class="acct-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-check2-all"></i></div>
        <div>
            <h4>Reconcile</h4>
            <p>Match your account balances with bank statements</p>
        </div>
    </div>
</div>

{{-- Reconcile form panel --}}
<div class="reconcile-panel">
    <div class="reconcile-panel-head">
        <div class="rp-icon" style="background:#eff6ff;color:#1d4ed8"><i class="bi bi-search"></i></div>
        <div>
            <h6>Start Reconciliation</h6>
            <small>Select an account and enter your statement closing balance</small>
        </div>
    </div>
    <div class="reconcile-panel-body">
        <form action="{{ url('accounting/reconcile/start') }}" method="POST" id="reconcile_form">
            @csrf
            <input type="hidden" name="chart_of_account_name" id="coa_name_hidden">

            <div class="row g-3">
                <div class="col-md-12">
                    <label class="acct-label">Account to Reconcile <span class="text-danger">*</span></label>
                    <select name="chart_of_account_id" id="reconcile_account" class="acct-control" required>
                        <option value="">— Select an account —</option>
                        @foreach($chart_of_accounts as $acct)
                        <option value="{{ $acct->id }}"
                            data-opening="{{ $acct->last_opening_balance }}"
                            data-name="{{ $acct->name }}"
                            data-last-reconcile="{{ $acct->last_reconcile_transaction_id }}">
                            {{ $acct->name_with_subtype }}
                        </option>
                        @endforeach
                    </select>
                    @error('chart_of_account_id')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Account summary (shown after selection) --}}
            <div id="account_summary" class="row g-3 mt-1" style="display:none!important">
                <div class="col-md-4">
                    <label class="acct-label">Opening Balance</label>
                    <div class="input-group acct-ig">
                        <span class="input-group-text"><i class="bi bi-wallet2"></i></span>
                        <input type="text" name="opening_balance" id="opening_balance_display"
                            class="acct-control" readonly style="background:#f8fafc">
                    </div>
                    @error('opening_balance')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="acct-label">Statement Ending Balance <span class="text-danger">*</span></label>
                    <div class="input-group acct-ig">
                        <span class="input-group-text"><i class="bi bi-bank"></i></span>
                        <input type="number" step="0.01" name="ending_balance" id="ending_balance"
                            class="acct-control" value="{{ old('ending_balance') }}" required placeholder="0.00">
                    </div>
                    @error('ending_balance')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="acct-label">Statement End Date <span class="text-danger">*</span></label>
                    <input type="text" name="ending_date" id="ending_date"
                        class="acct-control" value="{{ old('ending_date') }}" placeholder="YYYY-MM-DD" required>
                    @error('ending_date')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-3 mt-4" id="reconcile_actions" style="display:none!important">
                <button type="submit" class="btn-reconcile">
                    <i class="bi bi-check2-circle me-1"></i> Start Reconciliation
                </button>
                <button type="button" class="btn-undo" id="btn_undo" disabled
                    onclick="confirmUndo()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Undo Last Reconciliation
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Undo form (hidden) --}}
<form id="undo_last_reconcile_form" method="POST" action="{{ url('accounting/reconcile/undo') }}" class="d-none">
    @csrf
    <input type="hidden" name="chart_of_account_id" id="undo_account_id">
    <input type="hidden" name="last_reconcile_transaction_id" id="undo_last_txn_id">
    <input type="hidden" name="chart_of_account_name" id="undo_account_name">
</form>

{{-- Info card --}}
<div class="acct-card">
    <div class="acct-card-head">
        <h6><i class="bi bi-info-circle me-2 text-muted"></i>About Reconciliation</h6>
    </div>
    <div class="acct-card-body">
        <div class="row g-3">
            @php $tips = [
                ['bi-1-circle','Step 1 – Select Account','Choose the bank or cash account you want to reconcile against your statement.','blue'],
                ['bi-2-circle','Step 2 – Enter Balance','Enter the closing balance from your bank statement and the statement date.','green'],
                ['bi-3-circle','Step 3 – Match Entries','Review the list of journal entries and mark those that appear on your statement.','amber'],
                ['bi-4-circle','Step 4 – Finalise','Once the difference is zero, save the reconciliation.','violet'],
            ]; @endphp
            @foreach($tips as [$icon, $title, $desc, $colour])
            <div class="col-md-3">
                <div style="padding:16px;border-radius:10px;background:#f8fafc;height:100%">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        @php $cs = ['blue'=>['#eff6ff','#1d4ed8'],'green'=>['#f0fdf4','#15803d'],'amber'=>['#fffbeb','#b45309'],'violet'=>['#f5f3ff','#6d28d9']]; @endphp
                        <div style="background:{{ $cs[$colour][0] }};color:{{ $cs[$colour][1] }};border-radius:7px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">
                            <i class="bi {{ $icon }}"></i>
                        </div>
                        <span style="font-weight:600;font-size:.88rem;color:#1e293b">{{ $title }}</span>
                    </div>
                    <p style="font-size:.82rem;color:#64748b;margin:0;line-height:1.5">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
var accountData = @json($chart_of_accounts->keyBy('id'));

$(document).ready(function(){
    $('#reconcile_account').select2({placeholder:'— Search accounts —', width:'100%', allowClear:true});

    $('#reconcile_account').on('change', function(){
        var id = $(this).val();
        if(!id){ $('#account_summary,#reconcile_actions').hide(); return; }
        var acct = accountData[id];
        if(!acct) return;
        $('#opening_balance_display').val(acct.last_opening_balance||'0.00');
        document.getElementById('coa_name_hidden').value = acct.name || '';
        document.getElementById('undo_account_id').value  = id;
        document.getElementById('undo_account_name').value = acct.name || '';
        document.getElementById('undo_last_txn_id').value = acct.last_reconcile_transaction_id || '';
        var hasReconcile = !!acct.last_reconcile_transaction_id;
        $('#btn_undo').prop('disabled', !hasReconcile);
        $('#account_summary').css('display','flex');
        $('#reconcile_actions').css('display','flex');
    });

    // Pre-select old value
    @if(old('chart_of_account_id'))
    $('#reconcile_account').val('{{ old("chart_of_account_id") }}').trigger('change');
    @endif
});

function confirmUndo(){
    var name = document.getElementById('undo_account_name').value;
    if(confirm('Undo last reconciliation for "'+name+'"? This may not be reversible.')){
        document.getElementById('undo_last_reconcile_form').submit();
    }
}
</script>
@endsection
