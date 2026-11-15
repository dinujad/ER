@extends('accounting::layouts.app')
@section('title', 'Transfers')
@section('css')
@include('accounting::layouts._acct_theme')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
#transfers_table_wrapper .dataTables_filter input{border:1.5px solid #e2e8f0;border-radius:8px;padding:7px 12px;font-size:.88rem;outline:none}
#transfers_table_wrapper .dataTables_filter input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12)}
#transfers_table_wrapper .dataTables_length select{border:1.5px solid #e2e8f0;border-radius:8px;padding:5px 8px;font-size:.85rem}
#transfers_table_wrapper .dataTables_info{font-size:.83rem;color:#64748b}
#transfers_table_wrapper .page-link{border-radius:7px!important;border:1.5px solid #e2e8f0;color:#374151;font-size:.82rem}
#transfers_table_wrapper .page-item.active .page-link{background:#1e3a5f;border-color:#1e3a5f}
.arrow-chip{background:#eff6ff;color:#1d4ed8;border-radius:6px;padding:3px 8px;font-size:.8rem;font-weight:600}
/* Modal restyle */
#transferModal .modal-content{border-radius:14px;border:none;box-shadow:0 20px 60px rgba(0,0,0,.18)}
#transferModal .modal-header{background:linear-gradient(135deg,#1e3a5f,#0d2137);color:#fff;border-radius:14px 14px 0 0;padding:20px 24px}
#transferModal .modal-header .btn-close{filter:invert(1)}
#transferModal .modal-title{font-weight:700;font-size:1.1rem}
#transferModal .modal-body{padding:24px}
#transferModal .modal-footer{background:#f8fafc;border-radius:0 0 14px 14px;padding:16px 24px}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<div class="acct-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-arrow-left-right"></i></div>
        <div>
            <h4>Transfers</h4>
            <p>Move funds between accounts</p>
        </div>
    </div>
    <button type="button" class="btn-hero-action" data-bs-toggle="modal" data-bs-target="#transferModal">
        <i class="bi bi-plus-lg me-1"></i> New Transfer
    </button>
</div>

<div class="acct-card">
    <div class="acct-card-head">
        <h6><i class="bi bi-arrow-left-right me-2 text-muted"></i>Transfer Records</h6>
    </div>
    <div class="p-3">
        <div class="table-responsive">
            <table id="transfers_table" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th style="background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">ID</th>
                        <th style="background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Journal Entry</th>
                        <th style="background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Transfer From</th>
                        <th style="background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600"></th>
                        <th style="background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Transfer To</th>
                        <th style="background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Transferred By</th>
                        <th style="background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600;text-align:right">Amount</th>
                        <th style="background:#f8fafc;color:#475569;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Date</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Transfer modal --}}
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" id="transfer-modal-body">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>New Fund Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ url('accounting/transfers/store') }}" id="fund_transfer_form">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="acct-label">Business Location <span class="text-danger">*</span></label>
                            <select name="location_id" class="acct-control select2-modal" required>
                                <option value="">— Select —</option>
                                @foreach($business_locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="acct-label">Currency <span class="text-danger">*</span></label>
                            <select name="currency_id" class="acct-control select2-modal" required>
                                <option value="">— Select —</option>
                                @foreach($currencies as $cur)
                                <option value="{{ $cur->id }}">{{ $cur->currency }} ({{ $cur->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="acct-label">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="border:1.5px solid #e2e8f0;border-right:0;border-radius:8px 0 0 8px;background:#f8fafc;color:#64748b">LKR</span>
                                <input type="number" step="0.01" name="amount" class="acct-control" id="transfer_amount"
                                    style="border-radius:0 8px 8px 0;border-left:0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="acct-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="acct-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="acct-label">Payment Method</label>
                            <select name="payment_type_id" class="acct-control select2-modal">
                                @foreach($payment_types as $pt)
                                <option value="{{ $pt->id }}">{{ $pt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="row g-2 align-items-center">
                                <div class="col">
                                    <label class="acct-label">Transfer From <span class="text-danger">*</span></label>
                                    <select name="debit" id="transfer_from" class="acct-control select2-modal" required>
                                        <option value="">— Select account —</option>
                                        @foreach($chart_of_accounts as $acct)
                                        <option value="{{ $acct->id }}">{{ $acct->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-auto pt-3">
                                    <span class="arrow-chip mt-3 d-inline-block"><i class="bi bi-arrow-right"></i></span>
                                </div>
                                <div class="col">
                                    <label class="acct-label">Transfer To <span class="text-danger">*</span></label>
                                    <select name="credit" id="transfer_to" class="acct-control select2-modal" required>
                                        <option value="">— Select account —</option>
                                        @foreach($chart_of_accounts as $acct)
                                        <option value="{{ $acct->id }}">{{ $acct->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="same-account-err" class="text-danger mt-1" style="font-size:.82rem;display:none">
                                <i class="bi bi-exclamation-triangle me-1"></i>Transfer From and Transfer To cannot be the same account.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="acct-label">Reference</label>
                            <input type="text" name="reference" class="acct-control" placeholder="Optional reference">
                        </div>
                        <div class="col-md-6">
                            <label class="acct-label">Receipt / Transaction #</label>
                            <input type="text" name="receipt" class="acct-control" placeholder="e.g. TXN-001">
                        </div>
                        <div class="col-12">
                            <label class="acct-label">Notes</label>
                            <textarea name="notes" class="acct-control" rows="2" placeholder="Optional notes…"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-acct-save" id="transfer_submit_btn">
                        <i class="bi bi-check2-circle me-1"></i> Save Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function(){
    // DataTable
    var dt=$('#transfers_table').DataTable({
        processing:true,
        serverSide:true,
        ajax:'/accounting/transfers',
        columns:[
            {data:'id',name:'id',render:function(d){return '<span class="gl-badge">'+d+'</span>'}},
            {data:'journal_entry',name:'journal_entry'},
            {data:'transfer_from',name:'transfer_from',render:function(d){return '<span style="font-weight:500;color:#1e293b">'+d+'</span>'}},
            {data:'arrow_right',name:'arrow_right',orderable:false,searchable:false,render:function(){return '<span class="arrow-chip"><i class="bi bi-arrow-right"></i></span>'}},
            {data:'transfer_to',name:'transfer_to',render:function(d){return '<span style="font-weight:500;color:#1e293b">'+d+'</span>'}},
            {data:'transfer_by',name:'transfer_by',render:function(d){return '<span style="color:#64748b;font-size:.85rem">'+d+'</span>'}},
            {data:'amount',name:'amount',render:function(d){return '<span style="text-align:right;display:block;font-weight:600;color:#15803d">'+d+'</span>'}},
            {data:'created_at',name:'created_at',render:function(d){return '<span style="color:#64748b;font-size:.85rem;white-space:nowrap">'+d+'</span>'}}
        ]
    });

    // Validate same-account
    function validateAccounts(){
        var f=$('#transfer_from').val();
        var t=$('#transfer_to').val();
        var same=f&&t&&f===t;
        $('#same-account-err').toggle(same);
        $('#transfer_submit_btn').prop('disabled',same);
    }
    $('#transfer_from,#transfer_to').on('change',validateAccounts);

    // Select2 for modal
    $('#transferModal').on('shown.bs.modal',function(){
        $('.select2-modal').each(function(){
            if(!$(this).hasClass('select2-hidden-accessible'))
                $(this).select2({dropdownParent:$('#transferModal'),placeholder:'— Select —',width:'100%'});
        });
    });

    // AJAX submit
    $('#fund_transfer_form').on('submit',function(e){
        e.preventDefault();
        if($('#transfer_from').val()===$('#transfer_to').val()&&$('#transfer_from').val()){
            validateAccounts(); return;
        }
        $.ajax({
            method:'POST',url:$(this).attr('action'),data:$(this).serialize(),dataType:'json',
            success:function(r){
                if(r.success){
                    $('#transferModal').modal('hide');
                    dt.ajax.reload();
                    // show toast
                    var t=$('<div class="alert alert-success" style="position:fixed;top:20px;right:20px;z-index:9999;border-radius:10px;padding:14px 20px;min-width:260px;box-shadow:0 4px 16px rgba(0,0,0,.15)">'+
                        '<i class="bi bi-check-circle-fill me-2"></i>'+r.msg+'</div>').appendTo('body');
                    setTimeout(function(){t.fadeOut(400,function(){t.remove()})},3000);
                } else {
                    alert(r.msg||'An error occurred.');
                }
            },
            error:function(){alert('Server error. Please try again.');}
        });
    });
});
</script>
@endsection
