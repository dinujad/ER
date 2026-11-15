@extends('erp.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Recipe: {{ $productRecipe->name }}</h6>
                        <p class="text-sm mb-0">Product: {{ $productRecipe->product->name ?? 'N/A' }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        @canRoute('product-recipes.edit')
<a href="{{ route('product-recipes.edit', $productRecipe->id) }}" class="btn btn-primary btn-sm">Edit Recipe</a>
@endcanRoute
                        <a href="{{ route('product-recipes.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="border p-3 rounded">
                                <label class="text-xs text-muted text-uppercase font-weight-bold">Total Output</label>
                                <p class="mb-0 font-weight-bold">{{ $productRecipe->total_output_quantity }} {{ $productRecipe->output_unit }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border p-3 rounded">
                                <label class="text-xs text-muted text-uppercase font-weight-bold">Est Cost</label>
                                <p class="mb-0 font-weight-bold">LKR {{ number_format($productRecipe->production_cost, 2) }}</p>
                            </div>
                        </div>
                         <div class="col-md-3">
                            <div class="border p-3 rounded">
                                <label class="text-xs text-muted text-uppercase font-weight-bold">Wastage</label>
                                <p class="mb-0 font-weight-bold">{{ $productRecipe->wastage_percent }}%</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        @foreach($productRecipe->steps as $step)
                            <div class="card mb-3 border">
                                <div class="card-header p-3 bg-light">
                                    <h6 class="mb-0 text-dark">{{ $step->name }}</h6>
                                    @if($step->description) <small class="text-muted">{{ $step->description }}</small> @endif
                                </div>
                                <div class="card-body p-0">
                                    <table class="table align-items-center mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Material</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Quantity</th>
                                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Cost (Est)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($step->ingredients as $ingredient)
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm">{{ $ingredient->material->name ?? 'Material Not Found' }}</h6>
                                                    </div>
                                                </td>
                                                <td>
                                                    <p class="text-xs font-weight-bold mb-0">
                                                        {{ $ingredient->quantity }} {{ $ingredient->material->unit ?? '' }}
                                                    </p>
                                                </td>
                                                <td>
                                                    <p class="text-xs font-weight-bold mb-0">LKR {{ number_format($ingredient->cost, 2) }}</p>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach

                        @php
                            $materialsCost = $productRecipe->ingredientsMaterialCost();
                            $othersCost = $productRecipe->otherCostsTotal();
                        @endphp
                        @if($productRecipe->otherCosts->isNotEmpty())
                        <div class="card mb-3 border">
                            <div class="card-header p-3 bg-light">
                                <h6 class="mb-0 text-dark">Other Costs</h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-4">Description</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Cost (LKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($productRecipe->otherCosts as $otherCost)
                                        <tr>
                                            <td class="ps-4"><span class="text-sm">{{ $otherCost->description }}</span></td>
                                            <td><span class="text-sm font-weight-bold">{{ number_format($otherCost->amount, 2) }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                        <div class="card mb-3 border border-info">
                            <div class="card-header p-3 bg-light">
                                <h6 class="mb-0 text-dark">Cost Summary</h6>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-sm">Materials</span>
                                    <span class="text-sm font-weight-bold">LKR {{ number_format($materialsCost, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-sm">Other Costs</span>
                                    <span class="text-sm font-weight-bold">LKR {{ number_format($othersCost, 2) }}</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between">
                                    <span class="text-sm font-weight-bold">Total Recipe Cost</span>
                                    <span class="text-sm font-weight-bold">LKR {{ number_format($productRecipe->production_cost, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($productRecipe->instructions)
                    <div class="mt-4">
                        <h6 class="text-uppercase text-body text-xs font-weight-bolder mb-3">Instructions</h6>
                        <div class="p-3 bg-gray-100 rounded text-sm">
                            {!! nl2br(e($productRecipe->instructions)) !!}
                        </div>
                    </div>
                    @endif

                    <div class="mt-4">
                        @canRoute('product-recipes.destroy')
<form action="{{ route('product-recipes.destroy', $productRecipe->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this recipe?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Delete Recipe</button>
                        </form>
@endcanRoute
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
