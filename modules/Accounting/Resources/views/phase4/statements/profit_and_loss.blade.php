@extends('accounting::phase2._page')
@php $pageTitle = $pageTitle ?? 'Profit & Loss'; $pageSub = $pageSub ?? ($from . ' to ' . $to . ' · click an account to see its transactions'); @endphp
@section('page_body')
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Run</button></div>
</form>
<div class="table-responsive">
    <table class="acct-table table-sm">
        <thead><tr><th>GL</th><th>Name</th><th>Type</th><th class="text-end">Amount</th></tr></thead>
        <tbody>
            @forelse(($report['lines'] ?? []) as $l)
                @php $drill = url('accounting/phase6/account-transactions?gl=' . urlencode($l['gl_code']) . '&from=' . $from . '&to=' . $to); @endphp
                <tr>
                    <td><a href="{{ $drill }}">{{ $l['gl_code'] }}</a></td>
                    <td><a href="{{ $drill }}">{{ $l['name'] }}</a></td>
                    <td>{{ $l['account_type'] }}</td>
                    <td class="text-end">{{ is_numeric($l['amount']) ? number_format((float) $l['amount'], 2) : $l['amount'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted text-center py-3">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<p class="mt-3"><strong>Net:</strong> LKR {{ $report['net'] }}</p>
@endsection
