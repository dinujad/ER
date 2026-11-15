@extends('accounting::phase2._page')
@section('page_body')
<p>Trial balance OK: {{ $checklist['trial_balance_balanced'] ? 'Yes' : 'No' }}</p>
<p>Preview net income: LKR {{ number_format($preview['net_income'], 2) }}</p>
<form method="POST" action="{{ url('accounting/phase4/year-end/'.$id.'/execute') }}">@csrf
    <input type="hidden" name="retained_earnings_account_id" value="{{ $retainedId }}">
    <button class="btn btn-danger" {{ $checklist['ready'] ? '' : 'disabled' }}>Execute closing</button>
</form>
@endsection