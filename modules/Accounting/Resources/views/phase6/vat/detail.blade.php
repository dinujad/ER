@extends('accounting::phase2._page')

@php
    $titles = ['sales' => 'Taxable sales', 'returns' => 'Sale returns', 'credit_notes' => 'Credit notes', 'bills' => 'Bills with claimable VAT', 'bill_returns' => 'Purchase returns (input VAT reversed)'];
    $pageTitle = 'VAT return — ' . ($titles[$section] ?? $section);
    $pageSub = $from . ' to ' . $to;
@endphp

@section('page_actions')
    <a href="{{ url('accounting/phase6/vat-return?from=' . $from . '&to=' . $to) }}" class="btn btn-outline-secondary btn-sm">← Back to return</a>
@endsection

@section('page_body')
<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>Date</th><th>Document</th><th>{{ $isPurchase ? 'Supplier' : 'Customer' }}</th><th>TIN / VAT no.</th>
            @if($isPurchase)
                <th class="text-end">Value (excl. VAT)</th><th class="text-end">VAT</th>
            @else
                @if($section === 'sales')<th>Treatment</th>@endif
                <th class="text-end">Value (excl. tax)</th><th class="text-end">SSCL</th><th class="text-end">VAT</th>
            @endif
        </tr>
    </thead>
    <tbody>
    @forelse($rows as $r)
        <tr>
            <td class="text-nowrap">{{ $r->date }}</td>
            <td>@if($r->url)<a href="{{ $r->url }}">{{ $r->document }}</a>@else{{ $r->document }}@endif</td>
            <td>{{ $r->party }}</td>
            <td class="small">{{ $r->tin ?: '—' }}</td>
            @if($isPurchase)
                <td class="text-end font-monospace">{{ number_format($r->net, 2) }}</td>
                <td class="text-end font-monospace">{{ number_format($r->vat, 2) }}</td>
            @else
                @if($section === 'sales')<td class="small">{{ \App\Support\VatTreatment::label($r->treatment) }}</td>@endif
                <td class="text-end font-monospace">{{ number_format($r->goods, 2) }}</td>
                <td class="text-end font-monospace">{{ number_format($r->sscl, 2) }}</td>
                <td class="text-end font-monospace">{{ number_format($r->vat, 2) }}</td>
            @endif
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No documents in this period.</td></tr>
    @endforelse
    </tbody>
    @if($rows->isNotEmpty())
    <tfoot class="fw-bold">
        <tr>
            <td colspan="{{ $section === 'sales' ? 5 : 4 }}">Total ({{ $rows->count() }})</td>
            @if($isPurchase)
                <td class="text-end font-monospace">{{ number_format($rows->sum('net'), 2) }}</td>
                <td class="text-end font-monospace">{{ number_format($rows->sum('vat'), 2) }}</td>
            @else
                <td class="text-end font-monospace">{{ number_format($rows->sum('goods'), 2) }}</td>
                <td class="text-end font-monospace">{{ number_format($rows->sum('sscl'), 2) }}</td>
                <td class="text-end font-monospace">{{ number_format($rows->sum('vat'), 2) }}</td>
            @endif
        </tr>
    </tfoot>
    @endif
</table>
</div>
@endsection
