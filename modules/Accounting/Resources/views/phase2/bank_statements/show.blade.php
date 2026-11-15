@extends('accounting::phase2._page')

@php $pageTitle = 'Import #'.$import->id; $pageSub = $import->original_filename; @endphp

@section('page_body')
<table class="table table-sm">
    <thead><tr><th>Date</th><th>Description</th><th>Ref</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th>Match</th></tr></thead>
    <tbody>
    @foreach($import->lines as $line)
        <tr>
            <td>{{ optional($line->txn_date)->format('Y-m-d') }}</td>
            <td>{{ $line->description }}</td>
            <td>{{ $line->reference }}</td>
            <td class="text-end">{{ number_format((float)$line->debit, 2) }}</td>
            <td class="text-end">{{ number_format((float)$line->credit, 2) }}</td>
            <td>{{ $line->match_status }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<a href="{{ url('accounting/reconcile') }}" class="btn btn-sm btn-outline-primary">Open Reconciliation</a>
@endsection
