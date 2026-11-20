@extends('accounting::phase2._page')

@php $pageTitle = 'Journal ' . $txn['transaction_number']; $pageSub = $txn['name'] . ' · ' . $txn['date']; @endphp

@section('page_body')
@include('accounting::journal_entry.source_document', ['source' => $txn['source'], 'reference' => $txn['reference'], 'description' => $txn['name']])
<div class="mb-2 small">
    Type: {{ str_replace('_', ' ', (string) $txn['type']) }}
    @if($txn['reversed'])<span class="badge bg-secondary">Reversed</span>@endif
</div>
@include('accounting::phase6.partials.journal_list', ['journal' => collect([$txn])])
@php $d = collect($txn['lines'])->sum('debit'); $c = collect($txn['lines'])->sum('credit'); @endphp
<div class="small {{ abs($d - $c) < 0.005 ? 'text-success' : 'text-danger' }}">
    Debits {{ number_format($d, 2) }} · Credits {{ number_format($c, 2) }} · {{ abs($d - $c) < 0.005 ? 'Balanced' : 'NOT balanced' }}
</div>
@endsection
