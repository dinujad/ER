@extends('accounting::phase2._page')

@php $pageTitle = 'Audit Trail'; $pageSub = 'Who did what, when — invoices, payments, returns, bills, expenses, customers, suppliers, products'; @endphp

@section('page_actions')
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv"></i> Export CSV</a>
    <a href="{{ url('accounting/audit-log') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shield-check"></i> Accounting-module log</a>
@endsection

@section('page_body')
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-2">
        <label class="form-label small mb-0">User</label>
        <select name="user_id" class="form-select form-select-sm">
            <option value="">All users</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected($filters['user_id'] === $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-0">Document type</label>
        <select name="type" class="form-select form-select-sm">
            <option value="">All documents</option>
            @foreach($types as $key => $class)
                <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ ucwords(str_replace('_', ' ', $key)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-0">Action</label>
        <select name="event" class="form-select form-select-sm">
            <option value="">All actions</option>
            @foreach($events as $key => $label)
                <option value="{{ $key }}" @selected($filters['event'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-0">From</label>
        <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-0">To</label>
        <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-0">Search</label>
        <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Invoice no., user…">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
        <a href="{{ url('accounting/phase6/audit-trail') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle"></i> Reset</a>
    </div>
</form>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-light">
        <tr><th>Date &amp; time</th><th>User</th><th>Action</th><th>Document</th><th>What changed</th><th>IP</th></tr>
    </thead>
    <tbody>
    @forelse($logs as $log)
        @php $src = \App\Services\Accounting\SourceDocumentResolver::resolve($log->auditable_type, $log->auditable_id); $changes = $log->changes(); @endphp
        <tr>
            <td class="text-nowrap small">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
            <td class="small">{{ $log->user_name ?: 'System' }}</td>
            <td><span class="badge {{ in_array($log->event, ['deleted', 'invoice_voided'], true) ? 'bg-danger' : ($log->event === 'created' || str_contains($log->event, 'issued') ? 'bg-success' : 'bg-secondary') }}">{{ $log->eventLabel() }}</span></td>
            <td class="small">
                @if($src['url'])
                    <a href="{{ $src['url'] }}">{{ $log->document_label }}</a>
                @else
                    {{ $log->document_label }}
                @endif
                <div class="text-muted">{{ $log->documentTypeLabel() }}</div>
            </td>
            <td class="small">
                @if($log->event === 'updated' || count($changes) <= 3)
                    @foreach(array_slice($changes, 0, 4) as $c)
                        <div><span class="text-muted">{{ $c['field'] }}:</span> <span class="text-danger">{{ \Illuminate\Support\Str::limit($c['old'], 30) }}</span> → <span class="text-success">{{ \Illuminate\Support\Str::limit($c['new'], 30) }}</span></div>
                    @endforeach
                    @if(count($changes) > 4)<div class="text-muted">+ {{ count($changes) - 4 }} more</div>@endif
                @else
                    <span class="text-muted">{{ count($changes) }} fields</span>
                @endif
                @if($log->note)<div class="fst-italic">{{ $log->note }}</div>@endif
            </td>
            <td class="small text-muted">{{ $log->ip_address }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No audit entries match these filters.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@if(method_exists($logs, 'links')) {{ $logs->links() }} @endif
@endsection
