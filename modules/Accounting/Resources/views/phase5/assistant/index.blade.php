@extends('accounting::phase2._page')
@section('page_body')
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3">
    <div class="col-lg-8">
        <form method="post" action="{{ url('accounting/phase5/assistant') }}" class="mb-3" id="aiAskForm">
            @csrf
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label small">From</label>
                    <input type="date" name="from" class="form-control" value="{{ old('from', date('Y-m-01', strtotime('first day of last month'))) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">To</label>
                    <input type="date" name="to" class="form-control" value="{{ old('to', date('Y-m-t', strtotime('last day of last month'))) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Language</label>
                    <select name="locale" class="form-select">
                        <option value="">Auto</option>
                        <option value="en">English</option>
                        <option value="si">සිංහල</option>
                    </select>
                </div>
            </div>
            <label class="form-label">Question</label>
            <textarea name="question" class="form-control mb-2" rows="3" required maxlength="4000" placeholder="Ask in English or Sinhala…">{{ old('question') }}</textarea>
            <button class="btn btn-primary" type="submit" id="aiAskBtn">Ask (read-only)</button>
            <span class="text-muted small ms-2" id="aiLoading" style="display:none">Loading…</span>
        </form>

        @if(!empty($result))
            <div class="border rounded p-3 bg-light">
                <div class="small text-muted mb-2">
                    Provider: {{ $result['provider'] ?? '' }}
                    · Live: {{ !empty($result['live_provider']) ? 'yes' : 'no (deterministic/mock)' }}
                    · Status: {{ $result['status'] ?? '' }}
                    · Period: {{ $result['period']['from'] ?? '' }} → {{ $result['period']['to'] ?? '' }}
                </div>
                <pre class="mb-3" style="white-space:pre-wrap;font-family:inherit">{{ $result['reply'] ?? '' }}</pre>
                @if(!empty($result['source_refs']))
                    <div class="small"><strong>Sources</strong>
                        <ul class="mb-0">
                            @foreach($result['source_refs'] as $ref)
                                <li>
                                    {{ $ref['tool'] }} —
                                    @if(isset($ref['amount'])) LKR {{ number_format((float)$ref['amount'], 2) }} — @endif
                                    {{ $ref['is_estimate'] ? 'estimate' : 'actual' }}
                                    @if(!empty($ref['url'])) · <a href="{{ $ref['url'] }}">Open report</a>@endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="mb-3">
            <div class="fw-semibold mb-2">Suggested questions</div>
            @foreach($suggested ?? [] as $q)
                <button type="button" class="btn btn-sm btn-outline-secondary d-block mb-1 text-start w-100 suggest-q">{{ $q }}</button>
            @endforeach
        </div>
        <div>
            <div class="fw-semibold mb-2">History</div>
            @forelse($conversations ?? [] as $c)
                <a class="d-block small mb-1" href="{{ url('accounting/phase5/assistant/conversations/'.$c->id) }}">#{{ $c->id }} {{ $c->title }} ({{ $c->locale }})</a>
            @empty
                <div class="text-muted small">No conversations yet.</div>
            @endforelse
        </div>
    </div>
</div>
<script>
document.getElementById('aiAskForm')?.addEventListener('submit', function(){
  document.getElementById('aiLoading').style.display='inline';
  document.getElementById('aiAskBtn').disabled=true;
});
document.querySelectorAll('.suggest-q').forEach(function(btn){
  btn.addEventListener('click', function(){
    var ta=document.querySelector('textarea[name=question]');
    if(ta){ ta.value=btn.textContent; ta.focus(); }
  });
});
</script>
@endsection
