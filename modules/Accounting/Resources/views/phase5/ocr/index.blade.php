@extends('accounting::phase2._page')
@section('page_body')
<form method="post" action="{{ url('accounting/phase5/ocr') }}" enctype="multipart/form-data" class="mb-4 border rounded p-3">
    @csrf
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Document (JPG/PNG/PDF)</label>
            <input type="file" name="document" class="form-control" required accept=".jpg,.jpeg,.png,.pdf">
        </div>
        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="document_type" class="form-select">
                <option value="supplier_invoice">Supplier invoice</option>
                <option value="purchase_bill">Purchase bill</option>
                <option value="expense_receipt">Expense receipt</option>
                <option value="credit_note">Credit note</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Upload</button></div>
    </div>
    <div class="form-text">OCR creates review candidates only. No automatic journal posting. LKR only.</div>
</form>
<table class="table table-sm">
    <thead><tr><th>ID</th><th>File</th><th>Type</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($documents as $d)
        <tr>
            <td>{{ $d->id }}</td>
            <td>{{ $d->original_filename }}</td>
            <td>{{ $d->document_type }}</td>
            <td>{{ $d->status }}</td>
            <td><a href="{{ url('accounting/phase5/ocr/'.$d->id) }}">Open</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
