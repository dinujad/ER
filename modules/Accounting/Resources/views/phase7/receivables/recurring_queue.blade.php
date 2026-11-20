@extends('accounting::phase2._page')

@php $pageTitle = 'Recurring Queue'; $pageSub = 'Failed recurring runs to fix, and generated drafts waiting for you'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/recurring-journals') }}" class="btn btn-outline-secondary btn-sm">Templates</a>
    <a href="{{ url('accounting/recurring-journals/history') }}" class="btn btn-outline-secondary btn-sm">Full history</a>
@endsection

@section('page_body')
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

<div class="card mb-3 {{ $failed->isNotEmpty() ? 'border-danger' : '' }}"><div class="card-header"><strong>Failed runs</strong> <span class="small text-muted">({{ $failed->count() }}) — the template is stuck on that date until the run succeeds</span></div>
<div class="card-body p-0"><table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th>Date</th><th>Template</th><th>Error</th><th></th></tr></thead>
    <tbody>
    @forelse($failed as $r)
        <tr>
            <td class="text-nowrap">{{ $r->occurrence_date?->format('Y-m-d') }}</td>
            <td>{{ $templates[$r->template_id] ?? '#' . $r->template_id }}</td>
            <td class="small text-danger">{{ $r->message }}</td>
            <td class="text-end text-nowrap">
                <div class="acct-actions">
                    <a href="{{ url('accounting/recurring-journals') }}" class="btn btn-outline-secondary btn-xs"><i class="bi bi-pencil"></i> Fix template</a>
                    <form method="POST" action="{{ url('accounting/phase7/recurring-queue/' . $r->id . '/retry') }}" class="d-inline">@csrf<button class="btn btn-primary btn-xs"><i class="bi bi-arrow-repeat"></i> Retry</button></form>
                </div>
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-success small">No failed runs.</td></tr>
    @endforelse
    </tbody>
</table></div></div>

<div class="card"><div class="card-header"><strong>Drafts waiting for action</strong> <span class="small text-muted">({{ $drafts->count() }})</span></div>
<div class="card-body p-0"><table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th>Date</th><th>Template</th><th>Document</th><th>Status</th><th>Next step</th></tr></thead>
    <tbody>
    @forelse($drafts as $r)
        <tr>
            <td class="text-nowrap">{{ $r->occurrence_date?->format('Y-m-d') }}</td>
            <td>{{ $templates[$r->template_id] ?? '#' . $r->template_id }}</td>
            <td>{{ $r->doc['label'] }}</td>
            <td><span class="badge bg-secondary">{{ $r->doc['status'] }}</span></td>
            <td><div class="acct-actions justify-content-start">@foreach($r->doc['actions'] as [$label, $url])<a href="{{ $url }}" class="btn {{ $loop->first ? 'btn-primary' : 'btn-outline-secondary' }} btn-xs">{{ $label }}</a>@endforeach</div></td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted small">Nothing waiting.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="small text-muted mt-2">Recurring invoices are created as draft orders with the amount only — open the order, add the items, then Issue Invoice (stock and revenue post at issue).</div>
@endsection
