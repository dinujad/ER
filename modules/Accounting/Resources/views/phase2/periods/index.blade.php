@extends('accounting::phase2._page')

@php $pageTitle = 'Accounting Periods'; $pageSub = 'Fiscal controls · period lock'; @endphp

@section('page_body')
<form method="post" action="{{ url('accounting/periods') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-md-3"><input name="name" class="form-control form-control-sm" placeholder="Period name" required></div>
    <div class="col-md-2"><input type="date" name="starts_on" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><input type="date" name="ends_on" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary">Create Open Period</button></div>
</form>

<table class="table table-sm">
    <thead><tr><th>Name</th><th>Start</th><th>End</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($periods as $p)
        <tr>
            <td>{{ $p->name }}</td>
            <td>{{ optional($p->starts_on)->format('Y-m-d') }}</td>
            <td>{{ optional($p->ends_on)->format('Y-m-d') }}</td>
            <td>{{ $p->status }}</td>
            <td>
                @if($p->status==='open')
                    <form method="post" action="{{ url('accounting/periods/'.$p->id.'/close') }}" class="d-inline">@csrf<button class="btn btn-xs btn-sm btn-outline-danger">Close</button></form>
                @else
                    <form method="post" action="{{ url('accounting/periods/'.$p->id.'/reopen') }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Reopen</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No periods defined — posting allowed until periods are closed.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $periods->links() }}
@endsection
