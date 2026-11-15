@extends('accounting::phase2._page')
@php $pageTitle = 'Recurring Execution History'; $pageSub = 'Idempotent runs'; @endphp
@section('page_body')
<table class="table table-sm">
    <thead><tr><th>ID</th><th>Template</th><th>Date</th><th>Status</th><th>Generated</th><th>Message</th></tr></thead>
    <tbody>
    @foreach($runs as $r)
        <tr>
            <td>{{ $r->id }}</td>
            <td>{{ $r->template_id }}</td>
            <td>{{ optional($r->occurrence_date)->format('Y-m-d') }}</td>
            <td>{{ $r->status }}</td>
            <td>{{ $r->generated_type }} #{{ $r->generated_id }}</td>
            <td class="small">{{ $r->message }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
{{ $runs->links() }}
@endsection
