@extends('accounting::phase2._page')
@php $pageTitle = $pageTitle ?? 'Trial Balance'; $pageSub = $pageSub ?? ($from . ' to ' . $to . ' · click an account to see its transactions'); @endphp
@section('page_body')
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-2"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Run</button></div>
</form>
<div class="table-responsive">
    <table class="acct-table table-sm">
        <thead><tr><th>GL</th><th>Name</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
        <tbody>
            @forelse(($report['rows'] ?? []) as $r)
                @php $drill = url('accounting/phase6/account-transactions?gl=' . urlencode($r['gl_code']) . '&from=' . $from . '&to=' . $to); @endphp
                <tr>
                    <td><a href="{{ $drill }}">{{ $r['gl_code'] }}</a></td>
                    <td><a href="{{ $drill }}">{{ $r['name'] }}</a></td>
                    <td class="text-end">{{ is_numeric($r['debit']) ? number_format((float) $r['debit'], 2) : $r['debit'] }}</td>
                    <td class="text-end">{{ is_numeric($r['credit']) ? number_format((float) $r['credit'], 2) : $r['credit'] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted text-center py-3">No data</td></tr>
            @endforelse
        </tbody>
        @if(isset($report['total_debit']))
        <tfoot><tr class="fw-bold"><td colspan="2">Total</td><td class="text-end">{{ number_format((float) $report['total_debit'], 2) }}</td><td class="text-end">{{ number_format((float) $report['total_credit'], 2) }}</td></tr></tfoot>
        @endif
    </table>
</div>
@endsection
