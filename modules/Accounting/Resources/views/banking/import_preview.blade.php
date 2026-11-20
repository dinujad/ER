@extends('accounting::phase2._page')

@php $pageTitle = 'Check the import — ' . $acc->display_name; $pageSub = $meta['name'] . ' · nothing is saved until you confirm'; @endphp

@section('page_actions')
    <a href="{{ url('accounting/banking/' . $acc->id . '?tab=import') }}" class="btn btn-outline-secondary btn-sm">Back — change columns</a>
@endsection

@section('page_body')
<div class="row g-2 mb-3">
    <div class="col-6 col-md-2"><div class="card"><div class="card-body py-2"><div class="small text-muted">New lines</div><div class="fs-5 fw-bold text-success">{{ $preview['new'] }}</div></div></div></div>
    <div class="col-6 col-md-2"><div class="card"><div class="card-body py-2"><div class="small text-muted">Duplicates (skipped)</div><div class="fs-5 fw-bold text-warning">{{ $preview['duplicates'] }}</div></div></div></div>
    <div class="col-6 col-md-2"><div class="card"><div class="card-body py-2"><div class="small text-muted">Invalid (skipped)</div><div class="fs-5 fw-bold text-danger">{{ $preview['invalid'] }}</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2"><div class="small text-muted">Money in / out (new)</div><div class="fw-bold">{{ number_format($preview['credits'], 2) }} / {{ number_format($preview['debits'], 2) }}</div></div></div></div>
    <div class="col-12 col-md-3"><div class="card"><div class="card-body py-2"><div class="small text-muted">Dates</div><div class="fw-bold">{{ $preview['from'] ?? '—' }} → {{ $preview['to'] ?? '—' }}</div></div></div></div>
</div>

@if($preview['new'] > 0 && $preview['to'] && $preview['from'] && $preview['from'] > $preview['to'])
    <div class="alert alert-warning py-2">Dates look reversed — check the Date column.</div>
@endif

<div class="table-responsive mb-3" style="max-height:480px;overflow:auto">
<table class="table table-sm align-middle">
    <thead class="table-light"><tr><th>Row</th><th>Date</th><th>Description</th><th>Reference</th><th class="text-end">Out</th><th class="text-end">In</th><th>Status</th></tr></thead>
    <tbody>
    @foreach($preview['rows'] as $r)
        <tr class="{{ $r['status'] === 'new' ? '' : ($r['status'] === 'duplicate' ? 'table-warning' : 'table-danger') }}">
            <td class="small">{{ $r['row'] }}</td>
            <td class="small text-nowrap">{{ $r['date'] }}</td>
            <td class="small">{{ $r['description'] }}</td>
            <td class="small">{{ $r['reference'] }}</td>
            <td class="text-end font-monospace">{{ ($r['debit'] ?? 0) > 0 ? number_format($r['debit'], 2) : '' }}</td>
            <td class="text-end font-monospace">{{ ($r['credit'] ?? 0) > 0 ? number_format($r['credit'], 2) : '' }}</td>
            <td class="small">{{ $r['status'] === 'new' ? 'New' : $r['message'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>

<form method="POST" action="{{ url('accounting/banking/' . $acc->id . '/import/confirm') }}" class="d-flex gap-2 align-items-center">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <button class="btn btn-success" @disabled($preview['new'] === 0)>Import {{ $preview['new'] }} new line(s)</button>
    @if($meta['closing_balance'] !== null)<span class="small text-muted">Statement closing balance {{ number_format((float) $meta['closing_balance'], 2) }} will be saved for reconciliation.</span>@endif
</form>
@endsection
