@extends('accounting::phase2._page')
@section('page_body')
@include('accounting::phase4._report_table', [
    'headers' => ['GL', 'Name', 'Debit', 'Credit'],
    'rows' => collect($report['rows'] ?? [])->map(fn ($r) => [$r['gl_code'], $r['name'], $r['debit'], $r['credit']])->all(),
])
@endsection