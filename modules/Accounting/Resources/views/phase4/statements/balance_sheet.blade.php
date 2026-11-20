@extends('accounting::phase2._page')
@php $pageTitle = $pageTitle ?? 'Balance Sheet'; $pageSub = $pageSub ?? ('As of ' . $report['as_of'] . ' · click an account to see its transactions'); $fyStart = substr($report['as_of'], 0, 4) . '-01-01'; @endphp
@section('page_body')
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-2"><input type="date" name="as_of" value="{{ $report['as_of'] }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Run</button></div>
</form>
<p>As of {{ $report['as_of'] }} · Assets LKR {{ number_format($report['assets'], 2) }} · Liab+Equity LKR {{ number_format($report['liabilities_plus_equity'], 2) }}</p>
<p class="small text-muted">Current-period earnings included in equity: LKR {{ number_format($report['current_earnings'], 2) }}</p>
@foreach(['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity'] as $key => $label)
    <h6 class="mt-3">{{ $label }} <span class="text-muted">— LKR {{ number_format((float) ($report['totals'][$key] ?? 0), 2) }}</span></h6>
    <div class="table-responsive">
    <table class="acct-table table-sm">
        <tbody>
        @forelse(($report['sections'][$key] ?? []) as $row)
            @php $drill = url('accounting/phase6/account-transactions?gl=' . urlencode(($row['gl_code'] ?? '')) . '&from=' . $fyStart . '&to=' . $report['as_of']); @endphp
            <tr>
                <td style="width:15%"><a href="{{ $drill }}">{{ $row['gl_code'] ?? '' }}</a></td>
                <td><a href="{{ $drill }}">{{ $row['name'] ?? '' }}</a></td>
                <td class="text-end">{{ number_format((float) ($row['balance'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td class="text-muted">No balances</td></tr>
        @endforelse
        @if($key === 'equity')
            <tr class="text-muted"><td></td><td>Current-period earnings</td><td class="text-end">{{ number_format((float) $report['current_earnings'], 2) }}</td></tr>
        @endif
        </tbody>
    </table>
    </div>
@endforeach
@endsection
