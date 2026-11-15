@extends('accounting::phase2._page')
@section('page_body')
<p class="small">Type: {{ $task->task_type }} · Subject: {{ $task->subject_type }} #{{ $task->subject_id }} · Confidence: {{ $task->confidence }}</p>
<pre class="bg-light p-2 small">{{ json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
<form method="post" action="{{ url('accounting/phase5/review/'.$task->id.'/decide') }}" class="mb-3">@csrf
    <div class="mb-2"><label class="form-label">Note</label><textarea name="note" class="form-control" rows="2"></textarea></div>
    <button name="decision" value="accepted" class="btn btn-success btn-sm">Accept</button>
    <button name="decision" value="rejected" class="btn btn-outline-danger btn-sm">Reject</button>
    <button name="decision" value="clarification" class="btn btn-outline-secondary btn-sm">Request clarification</button>
</form>
<p class="small text-muted">Accepting does not post journals or bypass period locks / approvals.</p>
<h2 class="h6">Decision history</h2>
<ul>
@foreach($decisions as $d)
    <li>{{ $d->created_at }} — {{ $d->decision }} by user {{ $d->user_id }}: {{ $d->note }}</li>
@endforeach
</ul>
@endsection
