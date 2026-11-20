@extends('accounting::layouts.app')

@section('title', $pageTitle ?? 'Accounting')

@section('content')
<div class="acct-page">
    <div class="acct-card-head acct-page-head mb-3">
        <div>
            <h2 class="h4 fw-bold mb-0">{{ $pageTitle ?? '' }}</h2>
            @if(!empty($pageSub))
                <div class="text-muted small">{{ $pageSub }} · LKR</div>
            @endif
        </div>
        @hasSection('page_actions')
            <div class="d-flex flex-wrap gap-2">
                @yield('page_actions')
            </div>
        @endif
    </div>
    @yield('page_body')
</div>
@endsection
