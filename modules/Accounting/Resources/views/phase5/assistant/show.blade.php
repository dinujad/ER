@extends('accounting::phase2._page')
@section('page_body')
<a href="{{ url('accounting/phase5/assistant') }}" class="btn btn-sm btn-outline-secondary mb-3">Back</a>
@foreach($messages as $m)
<div class="border rounded p-2 mb-2 {{ $m->role==='user' ? '' : 'bg-light' }}">
    <div class="small text-muted">{{ $m->role }} · {{ $m->created_at }}</div>
    <pre class="mb-0" style="white-space:pre-wrap;font-family:inherit">{{ $m->content }}</pre>
</div>
@endforeach
@endsection
