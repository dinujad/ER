@extends('accounting::phase2._page')
@section('page_body')
<form method="post" action="{{ url('accounting/phase5/categorization') }}" class="border rounded p-3 mb-3">@csrf
    <div class="row g-2">
        <div class="col-md-5"><input name="description" class="form-control" placeholder="Expense description" required></div>
        <div class="col-md-2"><input name="amount" type="number" step="0.01" class="form-control" placeholder="Amount"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Suggest</button></div>
    </div>
</form>
<table class="table table-sm">
    <thead><tr><th>ID</th><th>Account</th><th>Confidence</th><th>Reason</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($suggestions as $s)
        <tr>
            <td>{{ $s->id }}</td>
            <td>{{ $s->suggested_account_id }}</td>
            <td>{{ $s->confidence }}%</td>
            <td class="small">{{ $s->reason }}</td>
            <td>{{ $s->status }}</td>
            <td>
                @if($s->status==='pending')
                <form method="post" action="{{ url('accounting/phase5/categorization/'.$s->id.'/decide') }}" class="d-inline">@csrf
                    <button name="decision" value="accepted" class="btn btn-sm btn-success">Accept</button>
                    <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                </form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
