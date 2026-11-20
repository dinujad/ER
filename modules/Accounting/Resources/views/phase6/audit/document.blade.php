@extends('accounting::phase2._page')

@php $pageTitle = 'History — ' . $source['label']; $pageSub = 'Every change to this document, newest first'; @endphp

@section('page_actions')
    @if($source['url'])
        <a href="{{ $source['url'] }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-up-right"></i> Open document</a>
    @endif
    <a href="{{ url('accounting/phase6/audit-trail') }}" class="btn btn-outline-secondary btn-sm">All audit entries</a>
@endsection

@section('page_body')
<div class="card"><div class="card-body">
    @include('accounting::phase6.partials.history', ['logs' => $logs])
</div></div>
@endsection
