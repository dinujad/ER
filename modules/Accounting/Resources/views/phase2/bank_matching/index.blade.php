@extends('accounting::phase2._page')

@php $pageTitle = 'Bank Transaction Matching'; $pageSub = 'Confirm matches · no auto GL'; @endphp

@section('page_actions')
<a href="{{ url('accounting/bank-reconciliation-report') }}" class="btn btn-sm btn-outline-secondary">Recon PDF</a>
<a href="{{ url('accounting/bank-statements') }}" class="btn btn-sm btn-outline-primary">Imports</a>
@endsection

@section('page_body')
<form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="chart_of_account_id" class="form-select form-select-sm" required>
            <option value="">Bank GL…</option>
            @foreach($accounts as $a)
                <option value="{{ $a->id }}" @selected($accountId==$a->id)>{{ $a->gl_code }} — {{ $a->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Search…"></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary">Load</button></div>
    <input type="hidden" name="tab" value="{{ $tab }}">
</form>

@if($accountId)
<ul class="nav nav-tabs mb-3">
    @foreach(['unmatched'=>'Unmatched','suggested'=>'Suggested','matched'=>'Matched','excluded'=>'Excluded','all'=>'All'] as $k=>$label)
        <li class="nav-item">
            <a class="nav-link {{ $tab===$k?'active':'' }}" href="{{ request()->fullUrlWithQuery(['tab'=>$k]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="table-responsive">
<table class="table table-sm align-middle">
    <thead><tr>
        <th>Date</th><th>Ref</th><th>Description</th>
        <th class="text-end">Debit</th><th class="text-end">Credit</th>
        <th>Status</th><th>Suggested / Match</th><th></th>
    </tr></thead>
    <tbody>
    @forelse($lines as $line)
        <tr>
            <td>{{ optional($line->txn_date)->format('Y-m-d') }}</td>
            <td>{{ $line->reference }}</td>
            <td>{{ $line->description }}</td>
            <td class="text-end">{{ number_format((float)$line->debit,2) }}</td>
            <td class="text-end">{{ number_format((float)$line->credit,2) }}</td>
            <td><span class="badge text-bg-secondary">{{ $line->match_status }}</span></td>
            <td class="small">
                @if($line->match_status==='matched')
                    {{ $line->matched_source_type }} #{{ $line->matched_source_id }}
                @elseif(!empty($suggestions[$line->id]))
                    @foreach($suggestions[$line->id] as $s)
                        <div class="mb-1">
                            {{ $s['description'] }} · LKR {{ $s['amount'] }}
                            · <strong>{{ $s['confidence'] }}%</strong> ({{ $s['reason'] }})
                            <form method="post" action="{{ url('accounting/bank-matching/'.$line->id.'/confirm') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="source_type" value="{{ $s['source_type'] }}">
                                <input type="hidden" name="source_id" value="{{ $s['source_id'] }}">
                                <button class="btn btn-xs btn-sm btn-success py-0">Confirm</button>
                            </form>
                        </div>
                    @endforeach
                @else
                    <span class="text-muted">No suggestions</span>
                @endif
            </td>
            <td class="text-nowrap">
                @if($line->match_status==='matched')
                    <form method="post" action="{{ url('accounting/bank-matching/'.$line->id.'/unmatch') }}">@csrf<button class="btn btn-sm btn-outline-warning">Unmatch</button></form>
                @elseif($line->match_status!=='excluded')
                    <form method="post" action="{{ url('accounting/bank-matching/'.$line->id.'/exclude') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Exclude</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No statement lines for filters.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $lines instanceof \Illuminate\Pagination\AbstractPaginator ? $lines->links() : '' }}
@endif
@endsection
