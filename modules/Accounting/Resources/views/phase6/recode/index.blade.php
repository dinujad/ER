@extends('accounting::phase2._page')

@php $pageTitle = 'Find & Recode'; $pageSub = 'Move journal lines that were posted to the wrong account — a reclassification journal is posted, nothing is deleted'; @endphp

@section('page_body')
@if(session('recode_skipped'))
    <div class="alert alert-warning py-2 small">@foreach(session('recode_skipped') as $s)<div>{{ $s }}</div>@endforeach</div>
@endif

<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-4">
        <label class="form-label small mb-0">Find lines on account</label>
        <select name="account_id" class="form-select form-select-sm" required>
            <option value="">Select account…</option>
            @foreach($accounts as $a)
                <option value="{{ $a->id }}" @selected($accountId == $a->id)>{{ $a->gl_code }} — {{ $a->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><label class="form-label small mb-0">From</label><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small mb-0">To</label><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small mb-0">Description contains</label><input name="contains" value="{{ $data['contains'] ?? '' }}" class="form-control form-control-sm"></div>
    @if($types->isNotEmpty())
    <div class="col-md-2"><label class="form-label small mb-0">Type</label>
        <select name="type" class="form-select form-select-sm"><option value="">Any</option>
            @foreach($types as $t)<option value="{{ $t }}" @selected(($data['type'] ?? '') === $t)>{{ str_replace('_', ' ', $t) }}</option>@endforeach
        </select>
    </div>
    @endif
    <div class="col-auto"><button class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Find</button></div>
</form>

@if($accountId)
<form method="POST" action="{{ url('accounting/phase6/recode') }}" onsubmit="return confirm('Post reclassification journals for the selected lines?');">
    @csrf
    <div class="row g-2 align-items-end mb-2">
        <div class="col-md-4">
            <label class="form-label small mb-0">Recode selected lines to</label>
            <select name="target_account_id" class="form-select form-select-sm" required>
                <option value="">Select new account…</option>
                @foreach($accounts as $a)
                    @if($a->id != $accountId)<option value="{{ $a->id }}">{{ $a->gl_code }} — {{ $a->name }}</option>@endif
                @endforeach
            </select>
        </div>
        <div class="col-md-5"><label class="form-label small mb-0">Reason (kept in the audit trail)</label><input name="reason" class="form-control form-control-sm" required maxlength="255"></div>
        <div class="col-auto"><button class="btn btn-warning btn-sm">Recode selected</button></div>
    </div>
    <div class="small text-muted mb-2">Lines in a locked period cannot be recoded (unlock the period first). If the source document is edited later, its journal is re-posted with the original account and must be recoded again.</div>
    <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
        <thead class="table-light"><tr>
            <th><input type="checkbox" onclick="document.querySelectorAll('.rc-sel').forEach(c => { if (!c.disabled) c.checked = this.checked; })"></th>
            <th>Date</th><th>Journal</th><th>Description</th><th>Source</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($lines as $l)
            <tr>
                <td><input type="checkbox" class="rc-sel" name="line_ids[]" value="{{ $l->id }}" @disabled($l->recoded_to)></td>
                <td class="text-nowrap small">{{ $l->date }}</td>
                <td class="small"><a href="{{ url('accounting/phase6/journal/' . urlencode($l->transaction_number)) }}">{{ $l->transaction_number }}</a></td>
                <td class="small">{{ $l->name }}</td>
                <td class="small">@if($l->source['url'])<a href="{{ $l->source['url'] }}">{{ $l->source['label'] }}</a>@else{{ $l->source['label'] }}@endif</td>
                <td class="text-end font-monospace">{{ (float) $l->debit > 0 ? number_format((float) $l->debit, 2) : '' }}</td>
                <td class="text-end font-monospace">{{ (float) $l->credit > 0 ? number_format((float) $l->credit, 2) : '' }}</td>
                <td class="small">@if($l->recoded_to)<a href="{{ url('accounting/phase6/journal/' . $l->recoded_to) }}" class="badge bg-secondary">recoded</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-muted">No lines found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</form>
@endif
@endsection
