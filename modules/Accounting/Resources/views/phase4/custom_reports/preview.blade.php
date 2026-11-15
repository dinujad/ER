@extends('accounting::phase2._page')
@section('page_body')
<pre class="small bg-light p-3">{{ json_encode($report, JSON_PRETTY_PRINT) }}</pre>
@endsection