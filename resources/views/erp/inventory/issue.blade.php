@extends('erp.layout')

@section('content')
<h2>Issue Material</h2>
<form action="{{ route('inventory.issue.store') }}" method="POST" class="bg-white p-4 rounded shadow-sm" style="max-width: 500px;">
    @csrf
    <div class="mb-3">
        <label>Select Material</label>
        <select name="material_id" class="form-select" required>
            @foreach($materials as $material)
                <option value="{{ $material->id }}">
                    {{ $material->name }} (Available: {{ $material->quantity }} {{ $material->unit }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label>Quantity to Issue</label>
        <input type="number" step="0.01" name="quantity_issued" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Issued To (Section/Person)</label>
        <input type="text" name="issued_to" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-warning">Issue Material</button>
</form>
@endsection
