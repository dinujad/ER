@extends('accounting::phase2._page')

@php
    $pageTitle = 'VAT & SSCL Return';
    $pageSub = $from . ' to ' . $to . ' · built from invoices, returns, credit notes and bills — click any figure for the documents';
    $q = 'from=' . $from . '&to=' . $to;
    $link = fn (string $section) => url('accounting/phase6/vat-return/' . $section . '?' . $q);
@endphp

@section('page_actions')
    <a href="{{ url('accounting/phase6/vat-return?' . $q . '&export=csv') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv"></i> Export CSV</a>
    <a href="{{ url('accounting/phase6/tax-invoice-register?' . $q) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-ol"></i> Tax invoice register</a>
@endsection

@section('page_body')
<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-2"><label class="form-label small mb-0">From</label><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label small mb-0">To</label><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm"></div>
    <div class="col-auto"><button class="btn btn-primary btn-sm">Run</button></div>
    <div class="col-auto small text-muted">Tip: Sri Lankan VAT and SSCL returns are filed per month or quarter — pick that period.</div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="small text-muted">Output VAT</div><div class="fs-4 fw-bold">{{ number_format($report['output_vat'], 2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="small text-muted">Input VAT (claimable)</div><div class="fs-4 fw-bold">{{ number_format($report['input_vat'], 2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card border-{{ $report['net_vat'] >= 0 ? 'danger' : 'success' }}"><div class="card-body">
        <div class="small text-muted">{{ $report['net_vat'] >= 0 ? 'Net VAT payable' : 'Net VAT refundable' }}</div><div class="fs-4 fw-bold">{{ number_format(abs($report['net_vat']), 2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body">
        <div class="small text-muted">SSCL payable</div><div class="fs-4 fw-bold">{{ number_format($report['sscl_payable'], 2) }}</div>
    </div></div></div>
</div>

<div class="card mb-3"><div class="card-header"><strong>Output tax — sales</strong></div><div class="card-body p-0">
<table class="table table-sm mb-0 align-middle">
    <thead class="table-light"><tr><th>Section</th><th class="text-end">Documents</th><th class="text-end">Value (excl. tax)</th><th class="text-end">SSCL</th><th class="text-end">VAT</th></tr></thead>
    <tbody>
    @foreach($report['by_treatment'] as $code => $t)
        <tr>
            <td><a href="{{ $link('sales') }}">Sales — {{ $t['label'] }}</a></td>
            <td class="text-end">{{ $t['count'] }}</td>
            <td class="text-end font-monospace">{{ number_format($t['goods'], 2) }}</td>
            <td class="text-end font-monospace">{{ number_format($t['sscl'], 2) }}</td>
            <td class="text-end font-monospace">{{ number_format($t['vat'], 2) }}</td>
        </tr>
    @endforeach
    <tr class="text-warning">
        <td><a href="{{ $link('returns') }}" class="text-warning">Less: sale returns</a></td>
        <td class="text-end">{{ $report['returns']['count'] }}</td>
        <td class="text-end font-monospace">({{ number_format($report['returns']['goods'], 2) }})</td>
        <td class="text-end font-monospace">({{ number_format($report['returns']['sscl'], 2) }})</td>
        <td class="text-end font-monospace">({{ number_format($report['returns']['vat'], 2) }})</td>
    </tr>
    <tr class="text-warning">
        <td><a href="{{ $link('credit_notes') }}" class="text-warning">Less: credit notes</a></td>
        <td class="text-end">{{ $report['credit_notes']['count'] }}</td>
        <td class="text-end font-monospace">({{ number_format($report['credit_notes']['goods'], 2) }})</td>
        <td class="text-end font-monospace">({{ number_format($report['credit_notes']['sscl'], 2) }})</td>
        <td class="text-end font-monospace">({{ number_format($report['credit_notes']['vat'], 2) }})</td>
    </tr>
    <tr class="fw-bold">
        <td>Total</td><td></td>
        <td class="text-end font-monospace">{{ number_format($report['sales']['goods'] - $report['returns']['goods'] - $report['credit_notes']['goods'], 2) }}</td>
        <td class="text-end font-monospace">{{ number_format($report['sscl_payable'], 2) }}</td>
        <td class="text-end font-monospace">{{ number_format($report['output_vat'], 2) }}</td>
    </tr>
    </tbody>
</table>
</div></div>

<div class="card mb-3"><div class="card-header"><strong>Input tax — purchases (bills marked "claim input VAT")</strong></div><div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead class="table-light"><tr><th>Section</th><th class="text-end">Documents</th><th class="text-end">Value (excl. VAT)</th><th class="text-end">VAT</th></tr></thead>
    <tbody>
        <tr><td><a href="{{ $link('bills') }}">Bills</a></td><td class="text-end">{{ $report['bills']['count'] }}</td><td class="text-end font-monospace">{{ number_format($report['bills']['net'], 2) }}</td><td class="text-end font-monospace">{{ number_format($report['bills']['vat'], 2) }}</td></tr>
        <tr class="text-warning"><td><a href="{{ $link('bill_returns') }}" class="text-warning">Less: purchase returns</a></td><td class="text-end">{{ $report['bill_returns']['count'] }}</td><td class="text-end font-monospace">({{ number_format($report['bill_returns']['net'], 2) }})</td><td class="text-end font-monospace">({{ number_format($report['bill_returns']['vat'], 2) }})</td></tr>
        <tr class="fw-bold"><td>Total input VAT</td><td></td><td></td><td class="text-end font-monospace">{{ number_format($report['input_vat'], 2) }}</td></tr>
    </tbody>
</table>
</div></div>

<div class="card mb-3"><div class="card-header"><strong>Ledger check</strong> <span class="small text-muted">— the return should equal the movement on the tax accounts</span></div><div class="card-body p-0">
<table class="table table-sm mb-0">
    <thead class="table-light"><tr><th>Account</th><th class="text-end">From documents</th><th class="text-end">Ledger movement</th><th class="text-end">Difference</th></tr></thead>
    <tbody>
    @foreach([
        ['2310 VAT Output Payable', $report['output_vat'], $report['ledger']['vat_output'], \App\Services\AccountingService::GL_VAT_OUTPUT],
        ['1165 VAT Input Receivable', $report['input_vat'], $report['ledger']['vat_input'], \App\Services\AccountingService::GL_VAT_INPUT],
        ['2320 SSCL Payable', $report['sscl_payable'], $report['ledger']['sscl'], \App\Services\AccountingService::GL_SSCL_PAYABLE],
    ] as [$label, $docs, $ledger, $gl])
        @php $diff = $ledger === null ? null : round($ledger - $docs, 2); @endphp
        <tr>
            <td><a href="{{ url('accounting/phase6/account-transactions?gl=' . $gl . '&' . $q) }}">{{ $label }}</a></td>
            <td class="text-end font-monospace">{{ number_format($docs, 2) }}</td>
            <td class="text-end font-monospace">{{ $ledger === null ? 'account missing' : number_format($ledger, 2) }}</td>
            <td class="text-end font-monospace {{ $diff !== null && abs($diff) > 0.009 ? 'text-danger fw-bold' : 'text-success' }}">{{ $diff === null ? '—' : number_format($diff, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="small text-muted p-2">A difference usually means a manual journal on a tax account, or invoices issued before tax was charged in the system (those post no tax). Click the account to see every line.</div>
</div></div>
@endsection
