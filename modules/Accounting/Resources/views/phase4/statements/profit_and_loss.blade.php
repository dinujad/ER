@extends('accounting::phase2._page')
@section('page_body')
@include('accounting::phase4._report_table', [
    'headers' => ['GL', 'Name', 'Type', 'Amount'],
    'rows' => collect($report['lines'] ?? [])->map(fn ($l) => [$l['gl_code'], $l['name'], $l['account_type'], $l['amount']])->all(),
])
<p class="mt-3"><strong>Net:</strong> LKR {{ $report['net'] }}</p>
@endsection