@extends('accounting::phase2._page')

@php $pageTitle = 'Bank Statement Imports'; $pageSub = 'CSV import · no auto-journals'; @endphp

@section('page_body')
<form method="post" action="{{ url('accounting/bank-statements') }}" enctype="multipart/form-data" class="row g-2 mb-4">
    @csrf
    <div class="col-md-4">
        <select name="chart_of_account_id" class="form-select form-select-sm" required>
            <option value="">Bank / cash GL…</option>
            @foreach($accounts as $a)
                <option value="{{ $a->id }}">{{ $a->gl_code }} — {{ $a->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><input type="file" name="file" accept=".csv,text/csv" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><button class="btn btn-sm btn-primary">Import CSV</button></div>
</form>
<p class="small text-muted">CSV headers: date, description, reference, debit, credit (or amount).</p>

<table class="table table-sm">
    <thead><tr><th>ID</th><th>File</th><th>Rows</th><th>Status</th><th>Imported</th><th></th></tr></thead>
    <tbody>
    @forelse($imports as $imp)
        <tr>
            <td>{{ $imp->id }}</td>
            <td>{{ $imp->original_filename }}</td>
            <td>{{ $imp->row_count }}</td>
            <td>{{ $imp->status }}</td>
            <td>{{ $imp->created_at }}</td>
            <td><a href="{{ url('accounting/bank-statements/'.$imp->id) }}">View</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No imports yet.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $imports->links() }}
@endsection
