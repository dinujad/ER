@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Fabric Issue (Create Job)</h6>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="p-4">
                        <form action="{{ route('jobs.store') }}" method="POST">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Style / Order Reference</label>
                                    <input type="text" name="style_ref" class="form-control border px-2" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Select Fabric</label>
                                    <select name="material_id" class="form-select border px-2" required>
                                        <option value="">Select Fabric...</option>
                                        @foreach($materials as $material)
                                            <option value="{{ $material->id }}">
                                                {{ $material->name }} (Available: {{ $material->quantity }} {{ $material->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Issue Quantity (meters/rolls)</label>
                                    <input type="number" step="0.01" name="issued_quantity" class="form-control border px-2" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Accessories (Details)</label>
                                    <textarea name="accessories" class="form-control border px-2" placeholder="Buttons, Zippers, etc."></textarea>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control border px-2"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Issue Fabric & Create Job</button>
                            <a href="{{ route('jobs.index') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
