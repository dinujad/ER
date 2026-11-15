@extends('accounting::phase2._page')
@php $pageTitle = 'Capitalization candidate #'.$candidate->id; @endphp
@section('page_body')
<p>PO #{{ $candidate->purchase_id }} · LKR {{ number_format((float)$candidate->amount,2) }} · <strong>{{ $candidate->status }}</strong></p>
@if($candidate->status==='pending_review')
<form method="post" action="{{ url('accounting/purchase-capitalization/'.$candidate->id.'/approve') }}">@csrf
    <button class="btn btn-sm btn-success">Approve</button>
</form>
@endif
@if(in_array($candidate->status,['pending_review','approved']))
<form method="post" action="{{ url('accounting/purchase-capitalization/'.$candidate->id.'/capitalize') }}" class="d-flex gap-2 mt-2">@csrf
    <input type="date" name="posting_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
    <button class="btn btn-sm btn-primary">Capitalize to FA register</button>
</form>
@endif
@if($candidate->asset_id)
<p class="mt-3"><a href="{{ url('accounting/fixed-assets/'.$candidate->asset_id) }}">View asset #{{ $candidate->asset_id }}</a></p>
@endif
@endsection