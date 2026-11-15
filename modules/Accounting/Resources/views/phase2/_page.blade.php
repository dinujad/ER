@extends('accounting::layouts.app')

@section('title', $pageTitle ?? 'Accounting')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-0">{{ $pageTitle ?? '' }}</h1>
            @if(!empty($pageSub))
                <div class="text-muted small">{{ $pageSub }} · LKR</div>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            @yield('page_actions')
        </div>
    </div>
    @yield('page_body')
</div>
@endsection
