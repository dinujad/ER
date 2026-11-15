<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $vehicles = Vehicle::with([
            'driver',
            'driverAssignments' => fn ($q) => $q->whereDate('assignment_date', $today)->with('driver'),
        ])->orderBy('name')->get();

        $drivers = Driver::where('status', 'active')->orderBy('name')->get();

        return view('erp.vehicles.index', compact('vehicles', 'drivers', 'today'));
    }

    public function create()
    {
        $drivers = Driver::where('status', 'active')->orderBy('name')->get();
        return view('erp.vehicles.create', compact('drivers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'number_plate' => 'required|string|max:50|unique:vehicles,number_plate',
            'type' => 'nullable|string|max:50',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        Vehicle::create([
            'name' => $request->name,
            'number_plate' => $request->number_plate,
            'type' => $request->type,
            'driver_id' => $request->driver_id,
        ]);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle added successfully.');
    }

    public function edit(Vehicle $vehicle)
    {
        $drivers = Driver::where('status', 'active')->orderBy('name')->get();
        $assignments = $vehicle->driverAssignments()
            ->with(['driver', 'assignedByUser'])
            ->whereDate('assignment_date', '>=', now()->subDays(7)->toDateString())
            ->whereDate('assignment_date', '<=', now()->addDays(30)->toDateString())
            ->orderBy('assignment_date')
            ->get();

        return view('erp.vehicles.edit', compact('vehicle', 'drivers', 'assignments'));
    }

    public function storeAssignment(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'assignment_date' => 'required|date',
            'driver_id' => 'required|exists:drivers,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($validated['assignment_date'])->toDateString();
        $driverId = (int) $validated['driver_id'];

        $conflict = VehicleDriverAssignment::query()
            ->with('vehicle')
            ->where('driver_id', $driverId)
            ->whereDate('assignment_date', $date)
            ->where('vehicle_id', '!=', $vehicle->id)
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'driver_id' => sprintf(
                    'This driver is already assigned to %s (%s) on %s.',
                    $conflict->vehicle->name,
                    $conflict->vehicle->number_plate,
                    Carbon::parse($date)->format('Y-m-d')
                ),
            ]);
        }

        VehicleDriverAssignment::updateOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'assignment_date' => $date,
            ],
            [
                'driver_id' => $driverId,
                'notes' => $validated['notes'] ?? null,
                'assigned_by' => auth()->id(),
            ]
        );

        return redirect()
            ->route('vehicles.edit', $vehicle)
            ->with('success', 'Driver assigned for ' . Carbon::parse($date)->format('d M Y') . '.')
            ->withFragment('daily-assignments');
    }

    public function destroyAssignment(Vehicle $vehicle, VehicleDriverAssignment $assignment)
    {
        if ($assignment->vehicle_id !== $vehicle->id) {
            abort(404);
        }

        $assignment->delete();

        return redirect()
            ->route('vehicles.edit', $vehicle)
            ->with('success', 'Daily assignment removed.')
            ->withFragment('daily-assignments');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'number_plate' => 'required|string|max:50|unique:vehicles,number_plate,' . $vehicle->id,
            'type' => 'nullable|string|max:50',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        $vehicle->update([
            'name' => $request->name,
            'number_plate' => $request->number_plate,
            'type' => $request->type,
            'driver_id' => $request->driver_id,
        ]);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted.');
    }
}
