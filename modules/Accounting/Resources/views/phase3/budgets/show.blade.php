@extends('accounting::phase2._page')
@php $pageTitle = $version->name; $pageSub = 'FY '.$version->financial_year.' v'.$version->version_no; @endphp
@section('page_body')
<div class="d-flex flex-wrap gap-2 mb-3">
    @if($version->status==='draft')
        <form method="post" action="{{ url('accounting/budget-versions/'.$version->id.'/submit') }}">@csrf<button class="btn btn-sm btn-outline-primary">Submit</button></form>
    @endif
    @if(in_array($version->status,['draft','submitted']))
        <form method="post" action="{{ url('accounting/budget-versions/'.$version->id.'/approve') }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
    @endif
    @if($version->status==='approved')
        <form method="post" action="{{ url('accounting/budget-versions/'.$version->id.'/activate') }}">@csrf<button class="btn btn-sm btn-primary">Activate</button></form>
    @endif
    <form method="post" action="{{ url('accounting/budget-versions/'.$version->id.'/revise') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Revise (copy)</button></form>
</div>
<table class="table table-sm"><thead><tr><th>Account</th><th>Annual</th><th>M1</th><th>M2</th><th>M3</th></tr></thead>
<tbody>
@foreach($version->lines as $line)
<tr>
    <td>{{ $line->chart_of_account_id }}</td>
    <td>{{ number_format((float)$line->annual_total,2) }}</td>
    <td>{{ number_format((float)$line->month_1,2) }}</td>
    <td>{{ number_format((float)$line->month_2,2) }}</td>
    <td>{{ number_format((float)$line->month_3,2) }}</td>
</tr>
@endforeach
</tbody></table>
@endsection