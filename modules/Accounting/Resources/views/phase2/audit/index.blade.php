@extends('accounting::phase2._page')

@php $pageTitle = 'Accounting Audit Log'; $pageSub = 'Immutable event history'; @endphp

@section('page_body')
<table class="table table-sm">
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Subject</th><th>Reason</th></tr></thead>
    <tbody>
    @forelse($events as $e)
        <tr>
            <td>{{ $e->created_at }}</td>
            <td>{{ $e->user_id }}</td>
            <td>{{ $e->action }}</td>
            <td class="small">{{ $e->subject_type }} #{{ $e->subject_id }}</td>
            <td>{{ $e->reason }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-muted">No audit events yet.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $events->links() }}
@endsection
