@extends('sales_person.layout')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Create New Order</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Order Details</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('sales-person.orders.store') }}">
                @csrf
                <div class="form-group mb-4">
                    <label for="customer_id">Select Customer</label>
                    <div class="input-group">
                        <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->display_name }} ({{ $customer->phone }})
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('sales-person.customers.create') }}" class="btn btn-outline-secondary">New Customer</a>
                    </div>
                     @error('customer_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <h5 class="mb-3">Products</h5>
                <div id="product-rows">
                    <div class="row mb-3 product-row">
                        <div class="col-md-6">
                            <select name="product_id[]" class="form-select" required>
                                <option value="">-- Select Product --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="1" value="1" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-row" style="display:none;">&times;</button>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-secondary mb-4" id="add-product">Add Another Product</button>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg">Place Order</button>
                    <a href="{{ route('sales-person.orders.index') }}" class="btn btn-secondary btn-lg">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
    document.getElementById('add-product').addEventListener('click', function() {
        var row = document.querySelector('.product-row').cloneNode(true);
        row.querySelector('input').value = 1;
        row.querySelector('.remove-row').style.display = 'block';
        document.getElementById('product-rows').appendChild(row);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('.row').remove();
        }
    });
</script>
@endsection

@endsection
