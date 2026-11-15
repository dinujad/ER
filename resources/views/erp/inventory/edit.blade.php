@extends('erp.layout')

@section('content')
<h2>Edit Material</h2>
@canRoute('inventory.update')
<form action="{{ route('inventory.update', $material->id) }}" method="POST" class="bg-white p-4 rounded shadow-sm" style="max-width: 500px;">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="{{ $material->name }}" required>
    </div>
    <div class="mb-3">
        <label>Type</label>
        <select name="type" class="form-select" required>
            <option value="fabric" {{ $material->type == 'fabric' ? 'selected' : '' }}>Fabric</option>
            <option value="accessory" {{ $material->type == 'accessory' ? 'selected' : '' }}>Accessory</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Quantity</label>
        <input type="number" step="0.01" name="quantity" class="form-control" value="{{ $material->quantity }}" required>
    </div>
    <div class="mb-3">
        <label>Unit Cost (Price per Unit)</label>
        <input type="number" step="0.01" name="unit_price" value="{{ $material->unit_price }}" class="form-control" placeholder="0.00">
    </div>
    <div class="mb-3">
        <label>Unit</label>
        <input type="text" name="unit" class="form-control" value="{{ $material->unit }}" required>
    </div>
    <div class="d-flex justify-content-between">
        <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Material</button>
    </div>
</form>
@endcanRoute
@endsection
