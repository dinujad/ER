@extends('accounting::phase2._page')
@section('page_body')
<p>Closing equity (incl. current earnings): LKR {{ number_format($report['closing_equity'], 2) }}</p>
@endsection