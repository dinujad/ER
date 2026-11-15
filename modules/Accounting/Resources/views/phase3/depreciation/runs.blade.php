@extends('accounting::phase2._page')
@php $pageTitle = 'Depreciation Runs'; $pageSub = 'Period posting'; @endphp
@section('page_actions')
    <a href="{{ url('accounting/depreciation/preview') }}" class="btn btn-outline-primary btn-sm">Preview period</a>
@endsection
@section('page_body')
<form method="post" action="{{ url('accounting/depreciation/runs') }}" class="row g-2 mb-4">
    @csrf
    <div class="col-auto"><input type="number" name="year" value="{{ date('Y') }}" class="form-control form-control-sm" min="2000" required></div>
    <div class="col-auto"><input type="number" name="month" value="{{ date('n') }}" class="form-control form-control-sm" min="1" max="12" required></div>
    <div class="col-auto"><input type="date" name="run_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Post run</button></div>
</form>
<table class="table table-sm">
    <thead><tr><th>Period</th><th>Run date</th><th>Status</th><th class="text-end">Total</th><th>Assets</th><th></th></tr></thead>
    <tbody>
    @forelse($runs as $r)
        <tr>
            <td>{{ $r->period_year }}-{{ str_pad($r->period_month,2,'0',STR_PAD_LEFT) }}</td>
            <td>{{ optional($r->run_date)->format('Y-m-d') }}</td>
            <td>{{ $r->status }}</td>
            <td class="text-end">{{ number_format((float)$r->total_amount,2) }}</td>
            <td>{{ $r->asset_count }}</td>
            <td>
                @if($r->status==='posted')
                <form method="post" action="{{ url('accounting/depreciation/runs/'.$r->id.'/reverse') }}" class="d-inline" onsubmit="return confirm('Reverse this depreciation run?');">@csrf
                    <button class="btn btn-xs btn-sm btn-outline-danger">Reverse</button>
                </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No depreciation runs yet.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $runs->links() }}
@endsection