<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleDailyRun;
use Illuminate\Http\Request;

class VehicleRunController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::orderBy('name')->get();
        $drivers = Driver::orderBy('name')->get();

        $vehicleId = $request->input('vehicle_id');
        $driverId = $request->input('driver_id');
        $date = $request->input('date', now()->format('Y-m-d'));

        $run = null;
        if ($vehicleId && $driverId && $date) {
            $run = VehicleDailyRun::with(['vehicle', 'driver', 'items'])
                ->where('vehicle_id', $vehicleId)
                ->where('driver_id', $driverId)
                ->whereDate('run_date', $date)
                ->orderByDesc('id')
                ->first();
        }

        return view('erp.vehicle-runs.index', compact('vehicles', 'drivers', 'run', 'vehicleId', 'driverId', 'date'));
    }
}
