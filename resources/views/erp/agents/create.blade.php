@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Add Sales Commission Agent</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('agents.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Agent Name</label>
                            <input type="text" name="agent_name" class="form-control border px-2" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" class="form-control border px-2" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Commission Type</label>
                                <select name="commission_type" class="form-select border px-2">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rate / Amount</label>
                                <input type="number" step="0.01" name="commission_rate" class="form-control border px-2" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Agent</button>
                        <a href="{{ route('agents.index') }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
