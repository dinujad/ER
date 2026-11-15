@extends('accounting::phase2._page')
@section('page_body')
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<form method="post" action="{{ url('accounting/phase5/alerts/run') }}" class="mb-3">@csrf
    <button class="btn btn-primary btn-sm">Run anomaly detection</button>
    <span class="small text-muted ms-2">Alerts are review indicators — not fraud accusations. No auto-reversals.</span>
</form>
<table class="table table-sm table-striped">
    <thead><tr><th>ID</th><th>Type</th><th>Severity</th><th>Amount</th><th>Detected</th><th>Status</th><th>Explanation</th><th></th></tr></thead>
    <tbody>
    @foreach($alerts as $a)
        <tr>
            <td>{{ $a->id }}</td>
            <td>{{ $a->alert_type }}</td>
            <td>{{ $a->severity }}</td>
            <td>@if($a->amount !== null) LKR {{ number_format((float)$a->amount, 2) }} @else — @endif</td>
            <td>{{ $a->detected_at }}</td>
            <td>{{ $a->status }}</td>
            <td class="small">{{ \Illuminate\Support\Str::limit($a->explanation, 120) }}</td>
            <td>
                <form method="post" action="{{ url('accounting/phase5/alerts/'.$a->id.'/status') }}" class="d-flex gap-1">@csrf
                    <select name="status" class="form-select form-select-sm">
                        @foreach(['new','under_review','resolved','dismissed'] as $s)
                            <option value="{{ $s }}" @selected($a->status===$s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-secondary">Save</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
