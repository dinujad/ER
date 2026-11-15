@extends('accounting::phase2._page')
@section('page_body')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<p class="small">Status: <strong>{{ $document->status }}</strong> · MIME {{ $document->mime_type }} · Draft: {{ $document->draft_source_type ?? 'none' }} #{{ $document->draft_source_id ?? '—' }}</p>
<p>
    <a class="btn btn-sm btn-outline-secondary" href="{{ url('accounting/phase5/ocr/'.$document->id.'/download') }}">Download original</a>
</p>
<form method="post" action="{{ url('accounting/phase5/ocr/'.$document->id.'/process') }}" class="mb-3">@csrf
    <button class="btn btn-sm btn-secondary">Re-run extraction</button>
</form>
@if($extraction)
<div class="mb-2 small">Confidence: {{ $extraction->confidence }}% · Currency: {{ $extraction->currency_flag }} · Amounts valid: {{ $extraction->amounts_valid ? 'yes' : 'no' }} · Duplicate: {{ $extraction->duplicate_suspected ? $extraction->duplicate_reason : 'no' }}</div>
@if($extraction->validation_errors)
    <div class="alert alert-warning small">{{ $extraction->validation_errors }}</div>
@endif
<form method="post" action="{{ url('accounting/phase5/ocr/'.$document->id.'/draft') }}">@csrf
    @foreach(['supplier_name','invoice_number','invoice_date','due_date','subtotal','tax_amount','discount','total'] as $f)
        <div class="mb-2">
            <label class="form-label small">{{ $f }}</label>
            <input class="form-control form-control-sm" name="fields[{{ $f }}]" value="{{ $fields[$f] ?? '' }}">
        </div>
    @endforeach
    <input type="hidden" name="fields[currency]" value="LKR">
    <button class="btn btn-primary btn-sm">Create draft (not posted)</button>
</form>
@else
<p class="text-muted">No extraction yet. Processing may be queued.</p>
@endif
@endsection
