@extends('accounting::layouts.app')
@section('title', $title)
@section('css')
@include('accounting::layouts._acct_theme')
<style>
.txn-num{font-family:monospace;font-size:.8rem;color:#475569;background:#f1f5f9;border-radius:5px;padding:2px 7px}
.module-tabs .btn{border-radius:20px;font-size:.85rem}
.module-tabs .btn.active{font-weight:600}
</style>
@endsection

@section('content')
@include('accounting::layouts.nav')

<div class="acct-hero">
    <div class="d-flex align-items-center gap-3">
        <div class="acct-hero-icon"><i class="bi bi-arrow-left-right"></i></div>
        <div>
            <h4>{{ $title }}</h4>
            <p>{{ $description }}</p>
        </div>
    </div>
    <a href="{{ url('accounting/journal_entry') }}" class="btn-hero-action">
        <i class="bi bi-journal-text me-1"></i> Full journal
    </a>
</div>

<div class="module-tabs d-flex flex-wrap gap-2 mb-3">
    <a href="{{ url('accounting/transactions/sales') }}"
       class="btn btn-sm {{ $module === 'sales' ? 'btn-primary active' : 'btn-outline-secondary' }}">
        Sales <span class="badge bg-light text-dark ms-1">{{ number_format($counts['sales'] ?? 0) }}</span>
    </a>
    <a href="{{ url('accounting/transactions/purchases') }}"
       class="btn btn-sm {{ $module === 'purchases' ? 'btn-primary active' : 'btn-outline-secondary' }}">
        Purchases <span class="badge bg-light text-dark ms-1">{{ number_format($counts['purchases'] ?? 0) }}</span>
    </a>
    <a href="{{ url('accounting/transactions/expenses') }}"
       class="btn btn-sm {{ $module === 'expenses' ? 'btn-primary active' : 'btn-outline-secondary' }}">
        Expenses <span class="badge bg-light text-dark ms-1">{{ number_format($counts['expenses'] ?? 0) }}</span>
    </a>
    <a href="{{ url('accounting/transactions/inventory') }}"
       class="btn btn-sm {{ $module === 'inventory' ? 'btn-primary active' : 'btn-outline-secondary' }}">
        Inventory <span class="badge bg-light text-dark ms-1">{{ number_format($counts['inventory'] ?? 0) }}</span>
    </a>
</div>

@unless($coaReady)
    <div class="alert alert-warning border-0 shadow-sm">
        <strong>Chart of accounts not ready.</strong>
        Seed COA then run accounting repair so ERP modules post to the journal:
        <code>/deploy/YOUR_KEY/seed-defaults</code> then
        <code>/deploy/YOUR_KEY/accounting-repair</code>.
    </div>
@endunless

@if($coaReady && $data->isEmpty())
    <div class="alert alert-info border-0 shadow-sm">
        No {{ strtolower($title) }} yet. New ERP activity posts here automatically.
        For older records run <code>/deploy/YOUR_KEY/accounting-repair</code>.
    </div>
@endif

<div class="filter-bar">
    <form method="GET" action="{{ Request::url() }}" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="acct-label">Entry type</label>
            <select name="transaction_type" class="acct-control">
                <option value="">All {{ $module }} types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(($typeFilter ?? '') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="acct-label">From</label>
            <input type="date" name="start_date" class="acct-control" value="{{ Request::get('start_date') }}">
        </div>
        <div class="col-md-2">
            <label class="acct-label">To</label>
            <input type="date" name="end_date" class="acct-control" value="{{ Request::get('end_date') }}">
        </div>
        <div class="col-md-3">
            <label class="acct-label">Search</label>
            <input type="text" name="s" class="acct-control" value="{{ Request::get('s') }}" placeholder="Txn #, name, reference">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn-acct-save d-flex align-items-center gap-2">
                <i class="bi bi-search"></i> Filter
            </button>
        </div>
    </form>
</div>

<div class="acct-card">
    <div class="acct-card-head">
        <h6><i class="bi bi-table me-2 text-muted"></i>Auto-posted from ERP</h6>
        <span class="chip chip-gray">{{ $data->count() }} lines{{ $data->count() >= 500 ? ' (showing latest 500)' : '' }}</span>
    </div>
    <div class="table-responsive">
        <table class="acct-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Txn #</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Account</th>
                    <th style="text-align:right">Debit</th>
                    <th style="text-align:right">Credit</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    @php
                        $debit = (float) ($row->debit ?? 0);
                        $credit = (float) ($row->credit ?? 0);
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;color:#475569">{{ $row->date }}</td>
                        <td><span class="txn-num">{{ $row->transaction_number }}</span></td>
                        <td><span class="chip chip-gray">{{ str_replace('_', ' ', $row->transaction_type) }}</span></td>
                        <td>
                            <div style="font-weight:500;color:#1e293b">{{ $row->name ?: '—' }}</div>
                            @if($row->reference)
                                <div class="text-muted small">{{ $row->reference }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight:500">{{ $row->account_name ?? '—' }}</div>
                            @if($row->gl_code)
                                <div class="text-muted small">GL {{ $row->gl_code }}</div>
                            @endif
                        </td>
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
                        <td>
                            <a href="{{ url('accounting/journal_entry/'.$row->transaction_number.'/show') }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:2rem"></i>
                            No {{ $module }} journal lines found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
