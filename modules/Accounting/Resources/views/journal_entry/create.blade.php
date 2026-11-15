@extends('accounting::layouts.app')
@section('title', 'New Journal Entry')
@section('css')
@include('accounting::layouts._acct_theme')
<style>
/* ── Row table ───────────────────────────── */
.je-row-table{width:100%;border-collapse:collapse;font-size:.88rem}
.je-row-table thead th{background:#f8fafc;color:#475569;font-weight:600;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;padding:10px 12px;border-bottom:1px solid #e9ecef;white-space:nowrap}
.je-row-table tbody td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.je-row-table tbody tr:last-child td{border-bottom:0}
.je-row-table tfoot td{background:#f8fafc;border-top:2px solid #e9ecef;padding:10px 12px;font-weight:700;font-size:.92rem}
.btn-row-add{background:#1e3a5f;color:#fff;border:none;border-radius:7px;padding:5px 12px;font-size:.8rem;cursor:pointer;transition:background .15s}
.btn-row-add:hover{background:#2563eb}
.btn-row-remove{background:#fef2f2;color:#dc2626;border:1.5px solid #fecaca;border-radius:7px;padding:5px 10px;font-size:.8rem;cursor:pointer;transition:all .15s}
.btn-row-remove:hover{background:#fee2e2}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<div class="acct-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-journal-plus"></i></div>
        <div>
            <h4>New Journal Entry</h4>
            <p>Create a double-entry journal transaction</p>
        </div>
    </div>
</div>

<form method="POST" action="{{ url('accounting/journal_entry/store') }}" id="je-form" novalidate>
    @csrf

    {{-- ① Header fields ──────────────────────────────── --}}
    <div class="acct-card">
        <div class="acct-card-head">
            <div class="d-flex align-items-center gap-2">
                <span style="background:#eff6ff;color:#1d4ed8;border-radius:7px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:.95rem">
                    <i class="bi bi-sliders2"></i>
                </span>
                <h6 class="mb-0">Transaction Details</h6>
            </div>
        </div>
        <div class="acct-card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="acct-label">Business Location <span class="text-danger">*</span></label>
                    <select name="location_id" id="location_id" class="acct-control select2-je" required>
                        <option value="">— Select —</option>
                        @foreach($business_locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                    @error('location_id')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="acct-label">Currency <span class="text-danger">*</span></label>
                    <select name="currency_id" id="currency_id" class="acct-control select2-je" required>
                        <option value="">— Select —</option>
                        @foreach($currencies as $cur)
                        <option value="{{ $cur->id }}" {{ old('currency_id') == $cur->id ? 'selected' : '' }}>
                            {{ $cur->currency }} ({{ $cur->code }})
                        </option>
                        @endforeach
                    </select>
                    @error('currency_id')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="acct-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" id="je_date" class="acct-control" value="{{ old('date', date('Y-m-d')) }}" required>
                    @error('date')<div class="text-danger mt-1" style="font-size:.8rem">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="acct-label">Payment Method</label>
                    <select name="payment_type_id" class="acct-control select2-je">
                        @foreach($payment_types as $pt)
                        <option value="{{ $pt->id }}" {{ old('payment_type_id') == $pt->id ? 'selected' : '' }}>{{ $pt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ② Journal rows ─────────────────────────────────── --}}
    <div class="acct-card">
        <div class="acct-card-head">
            <div class="d-flex align-items-center gap-2">
                <span style="background:#f0fdf4;color:#15803d;border-radius:7px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;font-size:.95rem">
                    <i class="bi bi-list-columns-reverse"></i>
                </span>
                <h6 class="mb-0">Journal Lines</h6>
            </div>
            <span class="chip chip-gray" id="row-count-badge">1 row</span>
        </div>
        <div class="table-responsive">
            <table class="je-row-table">
                <thead>
                    <tr>
                        <th width="28%"><i class="bi bi-arrow-down-circle me-1 text-danger"></i>Debit Account</th>
                        <th width="28%"><i class="bi bi-arrow-up-circle me-1 text-success"></i>Credit Account</th>
                        <th width="15%">Amount</th>
                        <th width="24%">Description</th>
                        <th width="5%" class="text-center">Act</th>
                    </tr>
                </thead>
                <tbody id="je-rows">
                    {{-- rows injected by JS --}}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end" style="color:#64748b;font-size:.85rem">Total Amount</td>
                        <td id="je-total" style="color:#1e3a5f">0.00</td>
                        <td colspan="2">
                            <button type="button" class="btn-row-add" id="btn-add-row">
                                <i class="bi bi-plus-lg me-1"></i> Add Row
                            </button>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Action bar --}}
    <div class="d-flex align-items-center justify-content-between" style="background:#fff;border:1px solid #e9ecef;border-radius:12px;padding:16px 20px;box-shadow:0 1px 4px rgba(0,0,0,.05)">
        <a href="{{ url('accounting/journal_entry') }}" style="color:#64748b;font-size:.9rem;text-decoration:none">
            <i class="bi bi-arrow-left me-1"></i> Back to list
        </a>
        <button type="submit" class="btn-acct-save">
            <i class="bi bi-check2-circle me-1"></i> Save Journal Entry
        </button>
    </div>
</form>
@endsection

@section('javascript')
<script>
(function(){
    var accounts = @json($chart_of_accounts);
    var rowIndex  = 0;
    var oldData   = @json(old('journal_entry_data', []));

    function buildAccountOptions(selected){
        var html='<option value="">— Select account —</option>';
        accounts.forEach(function(a){
            html+='<option value="'+a.id+'"'+(String(a.id)===String(selected)?' selected':'')+'>'+a.name_with_subtype+'</option>';
        });
        return html;
    }

    function addRow(data){
        data = data || {};
        var i = rowIndex++;
        var tr = document.createElement('tr');
        tr.setAttribute('data-idx', i);
        tr.innerHTML =
            '<td><select name="journal_entry_data['+i+'][debit]" class="acct-control select2-je-acct" required>'+buildAccountOptions(data.debit||'')+'</select></td>'+
            '<td><select name="journal_entry_data['+i+'][credit]" class="acct-control select2-je-acct" required>'+buildAccountOptions(data.credit||'')+'</select></td>'+
            '<td><input type="number" step="0.01" min="0" name="journal_entry_data['+i+'][amount]" class="acct-control je-amount" value="'+(data.amount||'')+'" placeholder="0.00" required></td>'+
            '<td><textarea name="journal_entry_data['+i+'][notes]" class="acct-control" rows="1" style="resize:vertical;min-height:38px" placeholder="Description…">'+(data.notes||'')+'</textarea></td>'+
            '<td class="text-center"><button type="button" class="btn-row-remove btn-remove-row" title="Remove"><i class="bi bi-trash"></i></button></td>';
        document.getElementById('je-rows').appendChild(tr);
        $(tr).find('.select2-je-acct').select2({placeholder:'— Search —', width:'100%'});
        updateTotal();
        updateRowCount();
    }

    function updateTotal(){
        var sum=0;
        document.querySelectorAll('.je-amount').forEach(function(inp){
            var v=parseFloat(inp.value);
            if(!isNaN(v)) sum+=v;
        });
        document.getElementById('je-total').textContent=sum.toLocaleString('en',{minimumFractionDigits:2,maximumFractionDigits:2});
    }

    function updateRowCount(){
        var n=document.querySelectorAll('#je-rows tr').length;
        document.getElementById('row-count-badge').textContent=n+' row'+(n!==1?'s':'');
    }

    document.getElementById('btn-add-row').addEventListener('click',function(){addRow()});

    document.getElementById('je-rows').addEventListener('click',function(e){
        var btn=e.target.closest('.btn-remove-row');
        if(!btn) return;
        var rows=document.querySelectorAll('#je-rows tr');
        if(rows.length<=1){alert('At least one journal line is required.');return;}
        btn.closest('tr').remove();
        updateTotal();
        updateRowCount();
    });

    document.getElementById('je-rows').addEventListener('input',function(e){
        if(e.target.classList.contains('je-amount')) updateTotal();
    });

    // Init
    $(document).ready(function(){
        $('.select2-je').select2({placeholder:'— Select —', width:'100%'});
        if(oldData.length>0){
            oldData.forEach(function(d){addRow(d)});
        } else {
            addRow();
        }
    });
})();
</script>
@endsection
