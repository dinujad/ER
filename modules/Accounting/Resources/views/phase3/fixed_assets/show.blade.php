@extends('accounting::phase2._page')
@php $pageTitle = $asset->asset_code; $pageSub = $asset->name.' · '.$asset->status; @endphp
@section('page_actions')
    @if($asset->status==='draft')
        <a href="{{ url('accounting/fixed-assets/'.$asset->id.'/edit') }}" class="btn btn-outline-secondary btn-sm">Edit</a>
    @endif
    <a href="{{ url('accounting/depreciation/assets/'.$asset->id.'/schedule') }}" class="btn btn-outline-primary btn-sm">Depreciation schedule</a>
    @if(in_array($asset->status,['active','fully_depreciated']))
        <a href="{{ url('accounting/asset-disposals?asset_id='.$asset->id) }}" class="btn btn-outline-danger btn-sm">Dispose</a>
    @endif
@endsection
@section('page_body')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="small text-muted">Category</div><div>{{ $asset->category->name ?? '—' }}</div></div>
    <div class="col-md-3"><div class="small text-muted">Cost / NBV</div><div>LKR {{ number_format((float)$asset->asset_cost,2) }} / {{ number_format((float)$asset->net_book_value,2) }}</div></div>
    <div class="col-md-3"><div class="small text-muted">Accum. depreciation</div><div>{{ number_format((float)$asset->accumulated_depreciation,2) }}</div></div>
    <div class="col-md-3"><div class="small text-muted">In service</div><div>{{ optional($asset->in_service_date)->format('Y-m-d') ?? '—' }}</div></div>
</div>
<div class="d-flex flex-wrap gap-2 mb-4">
    @if($asset->status==='draft')
        <form method="post" action="{{ url('accounting/fixed-assets/'.$asset->id.'/submit') }}">@csrf<button class="btn btn-sm btn-outline-primary">Submit</button></form>
    @endif
    @if($asset->status==='submitted')
        <form method="post" action="{{ url('accounting/fixed-assets/'.$asset->id.'/approve') }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
    @endif
    @if(in_array($asset->status,['draft','approved','submitted']))
        <form method="post" action="{{ url('accounting/fixed-assets/'.$asset->id.'/capitalize') }}" class="d-flex gap-2 align-items-center">@csrf
            <input type="date" name="posting_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
            <button class="btn btn-sm btn-primary">Capitalize</button>
        </form>
    @endif
    @if(in_array($asset->status,['active','fully_depreciated','approved','draft']))
        <form method="post" action="{{ url('accounting/fixed-assets/'.$asset->id.'/deactivate') }}">@csrf<button class="btn btn-sm btn-outline-warning">Deactivate</button></form>
    @endif
</div>
@php $ccList = \App\Models\Accounting\AcctCostCenter::query()->where('status','active')->orderBy('code')->limit(200)->get(); @endphp
@if($ccList->isNotEmpty())
<form method="post" action="{{ url('accounting/fixed-assets/'.$asset->id.'/transfer') }}" class="row g-2 align-items-end mb-4">@csrf
    <div class="col-auto"><label class="form-label small mb-0">Transfer to cost center</label>
        <select name="cost_center_id" class="form-select form-select-sm"><option value="">Select…</option>
            @foreach($ccList as $cc)<option value="{{ $cc->id }}" @selected($asset->cost_center_id==$cc->id)>{{ $cc->code }}</option>@endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Transfer</button></div>
</form>
@endif
<h2 class="h6 mt-4">Documents</h2>
<div class="d-flex flex-wrap gap-2 mb-2">
    <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/fixed-assets/'.$asset->id.'/reports/history/pdf') }}">History PDF</a>
    <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/fixed-assets/'.$asset->id.'/reports/depreciation/xlsx') }}">Schedule Excel</a>
</div>
<form method="post" action="{{ url('accounting/fixed-assets/'.$asset->id.'/attachments') }}" enctype="multipart/form-data" class="row g-2 mb-3">@csrf
    <div class="col-md-4"><input type="file" name="file" class="form-control form-control-sm" required></div>
    <div class="col-md-3"><input type="text" name="document_type" class="form-control form-control-sm" placeholder="Type"></div>
    <div class="col-md-3"><input type="text" name="description" class="form-control form-control-sm" placeholder="Description"></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Upload</button></div>
</form>
@if(isset($asset->attachments) && $asset->attachments->isNotEmpty())
<table class="table table-sm"><thead><tr><th>File</th><th>Type</th><th></th></tr></thead><tbody>
@foreach($asset->attachments as $att)
<tr><td>{{ $att->original_name }}</td><td>{{ $att->document_type }}</td>
<td class="text-end">
<a href="{{ url('accounting/fixed-assets/'.$asset->id.'/attachments/'.$att->id.'/download') }}">Download</a>
</td></tr>
@endforeach
</tbody></table>
@endif
@if($asset->events->isNotEmpty())
<h2 class="h6">Event history</h2>
<table class="table table-sm"><thead><tr><th>Date</th><th>Type</th><th>Description</th></tr></thead><tbody>
@foreach($asset->events->take(20) as $ev)
<tr><td>{{ $ev->event_date }}</td><td>{{ $ev->event_type }}</td><td>{{ $ev->description }}</td></tr>
@endforeach
</tbody></table>
@endif
@endsection