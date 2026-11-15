<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SalesPersonCommissionController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('sales-person.sales.index', array_filter([
            'month' => $request->input('month'),
        ]));
    }
}
