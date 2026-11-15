@extends('accounting::phase2._page')
@php $pageTitle = 'Job cost sheet'; $pageSub = $job->job_number ?? ('#'.$job->id); @endphp
@section('page_body')
<div class="row g-3 mb-4">
    <div class="col-md-3">Material: {{ number_format((float)$summary['material_total'],2) }}</div>
    <div class="col-md-3">Additional: {{ number_format((float)$summary['additional_total'],2) }}</div>
    <div class="col-md-3">Total: <strong>{{ number_format((float)$summary['total_cost'],2) }}</strong></div>
    <div class="col-md-3">Cost / unit: {{ number_format((float)$summary['cost_per_unit'],4) }}</div>
</div>
<h2 class="h6">Ledger lines (job_id)</h2>
<table class="table table-sm"><thead><tr><th>Date</th><th>Txn</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
<tbody>
@forelse($ledgerLines as $line)
<tr><td>{{ $line['date'] }}</td><td>{{ $line['transaction_number'] }}</td>
<td class="text-end">{{ number_format($line['debit'],2) }}</td>
<td class="text-end">{{ number_format($line['credit'],2) }}</td></tr>
@empty
<tr><td colspan="4" class="text-muted">No journal lines tagged to this job.</td></tr>
@endforelse
</tbody></table>
@endsection