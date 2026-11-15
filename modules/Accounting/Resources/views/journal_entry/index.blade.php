@extends('accounting::layouts.app')
@section('title', 'Journal Entries')
@section('css')
@include('accounting::layouts._acct_theme')
<style>
.txn-num{font-family:monospace;font-size:.8rem;color:#475569;background:#f1f5f9;border-radius:5px;padding:2px 7px}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

{{-- Hero --}}
<div class="acct-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-journal-text"></i></div>
        <div>
            <h4>Journal Entries</h4>
            <p>View and manage all double-entry journal records</p>
        </div>
    </div>
    @can('accounting.journal_entries.create')
    <a href="{{ url('accounting/journal_entry/create') }}" class="btn-hero-action">
        <i class="bi bi-plus-lg me-1"></i> New Journal Entry
    </a>
    @endcan
</div>

{{-- Filter bar --}}
<div class="filter-bar">
    <form method="GET" action="{{ Request::url() }}" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="acct-label">Account</label>
            <select name="chart_of_account_id" id="filter_account" class="acct-control select2-filter">
                <option value="">All accounts</option>
                @foreach($chart_of_accounts as $acct)
                <option value="{{ $acct->id }}" {{ Request::get('chart_of_account_id') == $acct->id ? 'selected' : '' }}>
                    {{ $acct->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="acct-label">From Date</label>
            <input type="date" name="start_date" class="acct-control" value="{{ Request::get('start_date') }}">
        </div>
        <div class="col-md-2">
            <label class="acct-label">To Date</label>
            <input type="date" name="end_date" class="acct-control" value="{{ Request::get('end_date') }}">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn-acct-save d-flex align-items-center gap-2">
                <i class="bi bi-search"></i> Filter
            </button>
            @if(count(array_filter(Request::only(['chart_of_account_id','start_date','end_date']))))
            <a href="{{ Request::url() }}" class="btn btn-outline-secondary rounded-3" style="border-radius:9px!important;padding:10px 16px;font-size:.9rem;">
                <i class="bi bi-x-lg"></i> Clear
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Table card --}}
<div class="acct-card">
    <div class="acct-card-head">
        <h6><i class="bi bi-table me-2 text-muted"></i>Journal Records</h6>
        <span class="chip chip-gray">{{ count($data) }} entries</span>
    </div>
    <div class="table-responsive">
        <table class="acct-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Entry ID</th>
                    <th>Transaction #</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Account</th>
                    <th>Subtype</th>
                    <th>Detail Type</th>
                    <th style="text-align:right">Debit</th>
                    <th style="text-align:right">Credit</th>
                    <th>Created By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $key)
                @php
                    $typeChips = ['asset'=>'chip-asset','income'=>'chip-income','expense'=>'chip-expense','liability'=>'chip-liability','equity'=>'chip-equity'];
                    $typeChip = $typeChips[$key->account_type] ?? 'chip-gray';
                    $debit  = (float)($key->debit ?? 0);
                    $credit = (float)($key->credit ?? 0);
                @endphp
                <tr>
                    <td>
                        <div class="acct-dd">
                            <button class="acct-dd-btn" onclick="acctToggleMenu(this)">
                                Action <i class="bi bi-chevron-down" style="font-size:.65rem"></i>
                            </button>
                            <div class="acct-dd-menu">
                                <a href="{{ url('accounting/journal_entry/'.$key->transaction_number.'/show') }}">
                                    <i class="bi bi-eye text-muted"></i> View
                                </a>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="{{ url('accounting/journal_entry/'.$key->transaction_number.'/show') }}"
                           style="color:#1e3a5f;font-weight:600;text-decoration:none">
                            #{{ $key->id }}
                        </a>
                    </td>
                    <td><span class="txn-num">{{ $key->transaction_number }}</span></td>
                    <td style="color:#475569;white-space:nowrap">{{ $key->date }}</td>
                    <td><span class="chip {{ $typeChip }}">{{ ucfirst($key->account_type) }}</span></td>
                    <td style="font-weight:500;color:#1e293b">{{ $key->account_name }}</td>
                    <td style="color:#64748b;font-size:.85rem">{{ $key->account_subtype }}</td>
                    <td style="color:#64748b;font-size:.85rem">{{ $key->account_detail_type }}</td>
                    <td style="text-align:right">
                        @if($debit > 0)
                            <span class="num-debit">{{ number_format($debit, 2) }}</span>
                        @else
                            <span class="num-zero">—</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        @if($credit > 0)
                            <span class="num-credit">{{ number_format($credit, 2) }}</span>
                        @else
                            <span class="num-zero">—</span>
                        @endif
                    </td>
                    <td style="color:#64748b;font-size:.85rem">{{ $key->created_by }}</td>
                </tr>
                @empty
                <tr><td colspan="11" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox d-block mb-2" style="font-size:2rem"></i>
                    No journal entries found.
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function(){
    $('.select2-filter').select2({placeholder:'All accounts',allowClear:true,width:'100%'});
    // sync start/end date
    $('input[name=start_date]').on('change',function(){
        var end=$('input[name=end_date]');
        if(!end.val()||end.val()<$(this).val()) end.val($(this).val());
    });
});
</script>
@endsection
