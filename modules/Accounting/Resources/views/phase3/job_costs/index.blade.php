@extends('accounting::phase2._page')
@php $pageTitle = 'Job Costing'; $pageSub = 'Manufacturing jobs'; @endphp
@section('page_body')
<form class="row g-2 mb-3" method="get">
    <div class="col-auto"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Job number"></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Search</button></div>
</form>
<table class="table table-sm">
    <thead><tr><th>Job #</th><th>Status</th><th class="text-end">Total cost</th><th class="text-end">Ledger debits</th><th></th></tr></thead>
    <tbody>
    @forelse($jobs as $job)
        @php $s = $summaries[$job->id] ?? []; @endphp
        <tr>
            <td>{{ $job->job_number ?? $job->id }}</td>
            <td>{{ $job->status }}</td>
            <td class="text-end">{{ number_format((float)($s['total_cost'] ?? 0),2) }}</td>
            <td class="text-end">{{ number_format((float)($s['ledger_debits'] ?? 0),2) }}</td>
            <td><a href="{{ url('accounting/job-costs/'.$job->id) }}">Cost sheet</a></td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No manufacturing jobs in this environment.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $jobs->links() }}
@endsection