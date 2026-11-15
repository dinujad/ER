@extends('accounting::phase2._page')
@section('page_body')
<p>Opening LKR {{ number_format($run['opening_cash'] ?? 0, 2) }} → Closing LKR {{ number_format($run['closing_cash'] ?? 0, 2) }}</p>
@endsection