@extends('accounting::phase2._page')
@php $pageTitle = 'Budget vs actual'; $pageSub = 'Variance'; @endphp
@section('page_body')
<form class="row g-2 mb-3" method="get">
    <div class="col-md-5">
        <select name="version_id" class="form-select form-select-sm" required>
            <option value="">Select version…</option>
            @foreach($versions as $v)
                <option value="{{ $v->id }}" @selected($versionId==$v->id)>{{ $v->financial_year }} — {{ $v->name }} ({{ $v->status }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><input type="number" name="month" value="{{ $month }}" min="1" max="12" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Run report</button></div>
</form>
@if(isset($report['error']))
    <div class="alert alert-warning">{{ $report['error'] }}</div>
@elseif($report)
<table class="table table-sm">
    <thead><tr><th>Account</th><th class="text-end">Budget</th><th class="text-end">Actual</th><th class="text-end">Variance</th><th>Fav?</th></tr></thead>
    <tbody>
    @foreach($report['rows'] as $row)
        <tr>
            <td>{{ $row['chart_of_account_id'] }}</td>
            <td class="text-end">{{ $row['budget'] }}</td>
            <td class="text-end">{{ $row['actual'] }}</td>
            <td class="text-end">{{ $row['variance'] }}</td>
            <td>{{ $row['favorable'] ? 'Yes' : 'No' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif
@endsection