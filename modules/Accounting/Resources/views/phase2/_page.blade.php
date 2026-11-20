@extends('accounting::layouts.app')

@section('title', $pageTitle ?? 'Accounting')

@section('content')
<div class="acct-page">
    <div class="acct-page-header d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="acct-page-title">{{ $pageTitle ?? '' }}</h1>
            @if(!empty($pageSub))
                <p class="acct-page-sub">{{ $pageSub }} · LKR</p>
            @else
                <p class="acct-page-sub">Accounting Workspace · LKR</p>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @yield('page_actions')
        </div>
    </div>
    @yield('page_body')
</div>
@endsection
