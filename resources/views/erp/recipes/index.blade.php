@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>Product Recipes</h6>
                    <a href="{{ route('product-recipes.create') }}" class="btn btn-primary btn-sm float-end">Add New Recipe</a>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Recipe Name</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Product</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Output</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Cost</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recipes as $recipe)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $recipe->name }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <p class="text-xs font-weight-bold mb-0">{{ $recipe->product->name ?? 'Product N/A' }}</p>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="badge badge-sm bg-gradient-success">{{ $recipe->total_output_quantity }} {{ $recipe->output_unit }}</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-secondary text-xs font-weight-bold">{{ number_format($recipe->production_cost, 2) }}</span>
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route('product-recipes.show', $recipe->id) }}" class="text-secondary font-weight-bold text-xs me--2" data-bs-toggle="tooltip" title="View recipe">
                                            View
                                        </a>
                                        @canRoute('product-recipes.edit')
<a href="{{ route('product-recipes.edit', $recipe->id) }}" class="text-primary font-weight-bold text-xs" data-bs-toggle="tooltip" title="Edit recipe">
                                            Edit
                                        </a>
@endcanRoute
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
