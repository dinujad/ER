@extends('accounting::phase2._page')
@section('page_body')
<table class="table table-sm">
    <thead><tr><th>ID</th><th>Type</th><th>Title</th><th>Confidence</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($tasks as $t)
        <tr>
            <td>{{ $t->id }}</td>
            <td>{{ $t->task_type }}</td>
            <td>{{ $t->title }}</td>
            <td>{{ $t->confidence }}</td>
            <td>{{ $t->status }}</td>
            <td><a href="{{ url('accounting/phase5/review/'.$t->id) }}">Review</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
