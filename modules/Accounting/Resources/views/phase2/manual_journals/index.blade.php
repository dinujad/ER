@extends('accounting::phase2._page')

@php $pageTitle = $title ?? 'Manual Journals'; $pageSub = 'Multi-line journal workflow'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/manual-journals/create') }}" class="btn btn-primary btn-sm">New Journal</a>
    <a href="{{ url('accounting/manual-journals/approvals') }}" class="btn btn-outline-secondary btn-sm">Approval Queue</a>
@endsection

@section('page_body')
<form class="row g-2 mb-3" method="get">
    <div class="col-auto"><input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search…"></div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(['draft','submitted','approved','posted','rejected','reversed'] as $s)
                <option value="{{ $s }}" @selected(($status ?? '')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
</form>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead><tr>
        <th>Journal #</th><th>Date</th><th>Description</th><th>Status</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th></th>
    </tr></thead>
    <tbody>
    @forelse($journals as $j)
        <tr>
            <td>{{ $j->journal_number }}</td>
            <td>{{ optional($j->journal_date)->format('Y-m-d') }}</td>
            <td>{{ $j->description }}</td>
            <td><span class="badge text-bg-secondary">{{ $j->status }}</span></td>
            <td class="text-end">{{ $j->totalDebit() }}</td>
            <td class="text-end">{{ $j->totalCredit() }}</td>
            <td><a href="{{ url('accounting/manual-journals/'.$j->id) }}">View</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-muted">No journals found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
{{ $journals->links() }}
@endsection
