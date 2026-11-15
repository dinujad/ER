@extends('accounting::phase2._page')
@section('page_body')
@include('accounting::phase4._report_table', [
    'headers' => ['Code', 'Name', 'Rate', 'Net payable'],
    'rows' => collect($summary['lines'] ?? [])->map(fn ($l) => [$l['tax_code'], $l['name'], $l['rate'], $l['net_payable']])->all(),
])
@endsection