@extends('erp.layout')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">Add New Material</h4>
            <p class="text-muted mb-0">Create a raw material record to track inventory and purchasing accurately.</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('inventory.store') }}" method="POST" class="row g-3">
                @csrf

                <div class="col-md-7">
                    <label for="name" class="form-label fw-semibold">Material Name</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control"
                        placeholder="e.g. Cotton Twill Fabric"
                        required
                    >
                </div>

                <div class="col-md-5">
                    <label for="type" class="form-label fw-semibold">Type</label>
                    <select id="type" name="type" class="form-select" required>
                        <option value="fabric" {{ old('type', 'fabric') === 'fabric' ? 'selected' : '' }}>Fabric</option>
                        <option value="accessory" {{ old('type') === 'accessory' ? 'selected' : '' }}>Accessory</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="quantity" class="form-label fw-semibold">Initial Quantity</label>
                    <input
                        id="quantity"
                        type="number"
                        step="0.01"
                        min="0"
                        name="quantity"
                        value="{{ old('quantity', '0') }}"
                        class="form-control"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label for="unit" class="form-label fw-semibold">Unit</label>
                    <input
                        id="unit"
                        type="text"
                        name="unit"
                        value="{{ old('unit') }}"
                        class="form-control"
                        placeholder="e.g. yards, pcs, kg"
                        required
                    >
                </div>

                <div class="col-md-4">
                    <label for="unit_price" class="form-label fw-semibold">Unit Cost (Rs)</label>
                    <input
                        id="unit_price"
                        type="number"
                        step="0.01"
                        min="0"
                        name="unit_price"
                        value="{{ old('unit_price') }}"
                        class="form-control"
                        placeholder="0.00"
                    >
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                    <a href="{{ route('inventory.index') }}" class="btn btn-light border">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i> Save Material
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3">උපදෙස් (මෙතනින් Add කිරීම ගැන)</h6>
            <ul class="mb-3 ps-3">
                <li><strong>මෙතනින් Add කරන්නේ</strong> අලුත් material එකේ master record එක (නම, වර්ගය, unit, unit cost) create කිරීමටයි.</li>
                <li><strong>Initial Quantity</strong> එකට දැම්ම ගණනෙන් inventory opening stock එක set වෙයි.</li>
                <li><strong>Fabric / Accessory</strong> type එක හරියට තෝරන්න. Reports සහ filtering වලට ඒක වැදගත්.</li>
                <li><strong>Unit Cost</strong> optional. දාගත්තොත් ඇස්තමේන්තු වටිනාකම්/ගණන් බැලීමට උපකාරී වෙයි.</li>
            </ul>

            <h6 class="fw-bold mb-3">Purchase එකෙන් Add කරන එකට වෙනස</h6>
            <ul class="mb-0 ps-3">
                <li><strong>Inventory → Add Material:</strong> Material item එක define කරන place එක. Supplier invoice/payment entry නැහැ.</li>
                <li><strong>Purchases → Add Purchase:</strong> දැනට තියෙන materials ට stock <strong>increase</strong> කරන transactional entry එක.</li>
                <li><strong>Purchase entry</strong> එකේ supplier, quantities, unit costs, totals වගේ accounting details save වෙයි.</li>
                <li>සාමාන්‍ය ක්‍රමය: <strong>පළමුව material එක create කරන්න</strong> → පස්සේ <strong>Purchase</strong> හරහා stock ගොඩක් add කරන්න.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
