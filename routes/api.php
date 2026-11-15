<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HikvisionController;
use App\Models\Employee;

Route::post('/hikvision/events', [HikvisionController::class, 'handleEvent']);

Route::get('/employees', function (Request $request) {
    $q = Employee::query()->orderBy('name');
    if ($request->filled('search')) {
        $q->where('name', 'like', '%' . $request->search . '%');
    }
    $employees = $q->get(['id', 'name']);
    return response()->json(['data' => $employees]);
});
