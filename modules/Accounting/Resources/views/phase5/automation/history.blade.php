@extends('accounting::phase2._page')
@section('page_body')
<table class="table table-sm">
    <thead><tr><th>ID</th><th>Rule</th><th>Occurrence</th><th>Status</th><th>Started</th><th>Finished</th></tr></thead>
    <tbody>
    @foreach($executions as $e)
        <tr>
            <td>{{ $e->id }}</td><td>{{ $e->rule_id }}</td><td class="small">{{ $e->occurrence_key }}</td>
            <td>{{ $e->status }}</td><td>{{ $e->started_at }}</td><td>{{ $e->finished_at }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
