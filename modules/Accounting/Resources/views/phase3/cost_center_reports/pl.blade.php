@extends('accounting::phase2._page')
@php $pageTitle = 'Cost center P&L'; @endphp
@section('page_body')
<form class="row g-2 mb-3">
    <div class="col-auto"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><select name="cost_center_id" class="form-select form-select-sm">
        <option value="unassigned" @selected($ccParam==='unassigned')>Unassigned</option>
        @foreach($centers as $cc)<option value="{{ $cc->id }}" @selected((string)$ccParam===(string)$cc->id)>{{ $cc->code }} — {{ $cc->name }}</option>@endforeach
    </select></div>
    <div class="col-auto"><button class="btn btn-sm btn-primary">Run</button></div>
    <div class="col-auto"><a href="{{ url('accounting/cost-center-reports/profit-and-loss/pdf?from='.$from.'&to='.$to.'&cost_center_id='.$ccParam) }}" class="btn btn-sm btn-outline-secondary">PDF</a></div>
</form>
<p>Income LKR {{ $report['income'] }} · Expense LKR {{ $report['expense'] }} · Net LKR {{ $report['net'] }}</p>
<a href="{{ url('accounting/cost-center-reports/journals?from='.$from.'&to='.$to.'&cost_center_id='.$ccParam) }}">Drill-down journals</a>
<table class="table table-sm mt-3"><thead><tr><th>GL</th><th>Account</th><th>Amount</th></tr></thead><tbody>
@foreach($report['lines'] as $line)<tr><td>{{ $line['gl_code'] }}</td><td>{{ $line['name'] }}</td><td>{{ $line['amount'] }}</td></tr>@endforeach
</tbody></table>
@endsection