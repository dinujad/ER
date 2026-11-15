@extends('accounting::phase2._page')

@php $pageTitle = 'Edit '.$journal->journal_number; $pageSub = 'Draft editing'; @endphp

@section('page_body')
<form method="post" action="{{ url('accounting/manual-journals/'.$journal->id) }}">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">Journal date</label>
            <input type="date" name="journal_date" class="form-control" value="{{ old('journal_date', optional($journal->journal_date)->format('Y-m-d')) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Reference</label>
            <input type="text" name="reference" class="form-control" value="{{ old('reference', $journal->reference) }}">
        </div>
        <div class="col-md-6">
            <label class="form-label">Description</label>
            <input type="text" name="description" class="form-control" value="{{ old('description', $journal->description) }}">
        </div>
    </div>
    <div class="table-responsive mb-3">
        <table class="table table-sm">
            <thead><tr><th>Account</th><th>Debit</th><th>Credit</th><th>Memo</th></tr></thead>
            <tbody>
            @foreach($journal->lines as $i => $line)
                <tr>
                    <td>
                        <select name="lines[{{ $i }}][account_id]" class="form-select form-select-sm" required>
                            @foreach($accounts as $a)
                                <option value="{{ $a->id }}" @selected($a->id==$line->chart_of_account_id)>{{ $a->gl_code }} — {{ $a->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="lines[{{ $i }}][debit]" class="form-control form-control-sm" value="{{ $line->debit }}"></td>
                    <td><input type="number" step="0.01" name="lines[{{ $i }}][credit]" class="form-control form-control-sm" value="{{ $line->credit }}"></td>
                    <td><input type="text" name="lines[{{ $i }}][memo]" class="form-control form-control-sm" value="{{ $line->memo }}"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <button class="btn btn-primary">Update Draft</button>
</form>
@endsection
