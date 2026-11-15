<?php

namespace App\Http\Controllers;

use App\Models\VehicleDailyRun;
use App\Models\VehicleDailyRunItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverDashboardController extends Controller
{
    public function index(Request $request)
    {
        $driver = auth()->user()->driver;
        if (!$driver) {
            abort(403, 'Driver profile not found.');
        }
        $today = now()->format('Y-m-d');
        $vehicle = $driver->assignedVehicleForDate($today);
        // Current job = latest today with status started (one day can have multiple jobs)
        $run = VehicleDailyRun::with('items')
            ->where('driver_id', $driver->id)
            ->whereDate('run_date', $today)
            ->where('status', 'started')
            ->orderByDesc('id')
            ->first();
        $todayEndedCount = VehicleDailyRun::where('driver_id', $driver->id)->whereDate('run_date', $today)->where('status', 'ended')->count();

        return view('driver.dashboard', compact('driver', 'vehicle', 'run', 'today', 'todayEndedCount'));
    }

    /** Start new job: start meter photo + start point. Same day can have multiple jobs. */
    public function startDay(Request $request)
    {
        $driver = auth()->user()->driver;
        $vehicle = $driver?->assignedVehicleForDate(now()->format('Y-m-d'));
        if (!$driver || !$vehicle) {
            return redirect()->route('driver.dashboard')->with('error', 'No vehicle assigned for today. Please ask admin to assign you under Vehicle Management.');
        }

        $request->validate([
            'start_meter_photo' => 'required|image|max:5120',
            'start_point' => 'required|string|max:255',
        ]);

        $today = now()->format('Y-m-d');
        $path = $request->file('start_meter_photo')->store('vehicle-runs/start', 'public');

        VehicleDailyRun::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'run_date' => $today,
            'start_meter_photo_path' => $path,
            'start_route' => $request->start_point,
            'status' => 'started',
        ]);

        return redirect()->route('driver.dashboard')->with('success', 'Job started. Add expenses and end job when done.');
    }

    /** Add expense item (fuel, others) with slip and cost */
    public function addItem(Request $request)
    {
        $driver = auth()->user()->driver;
        if (!$driver) {
            abort(403);
        }

        $request->validate([
            'vehicle_daily_run_id' => 'required|exists:vehicle_daily_runs,id',
            'type' => 'required|in:fuel,toll,others',
            'name' => 'required|string|max:100',
            'cost' => 'required|numeric|min:0.01',
            'slip' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'redirect_to' => 'nullable|string|max:500',
        ]);

        $run = VehicleDailyRun::where('id', $request->vehicle_daily_run_id)->where('driver_id', $driver->id)->firstOrFail();
        if ($run->status === 'ended') {
            return redirect()->route('driver.dashboard')->with('error', 'Cannot add to ended run.');
        }

        $item = new VehicleDailyRunItem([
            'type' => $request->type,
            'name' => $request->name,
            'cost' => $request->cost,
        ]);
        $run->items()->save($item);

        if ($request->hasFile('slip')) {
            $item->slip_path = $request->file('slip')->store('vehicle-runs/items', 'public');
            $item->save();
        }

        return $this->redirectAfterDriverExpense($request)->with('success', 'Expense added.');
    }

    /** End day: end meter photo required */
    public function endDay(Request $request)
    {
        $driver = auth()->user()->driver;
        if (!$driver) {
            abort(403);
        }

        $request->validate([
            'vehicle_daily_run_id' => 'required|exists:vehicle_daily_runs,id',
            'end_meter_photo' => 'required|image|max:5120',
            'destination' => 'required|string|max:255',
            'end_km' => 'nullable|numeric|min:0',
            'google_map_link' => 'nullable|url|max:500',
            'distance_km' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $run = VehicleDailyRun::where('id', $request->vehicle_daily_run_id)->where('driver_id', $driver->id)->firstOrFail();
        if ($run->status === 'ended') {
            return redirect()->route('driver.dashboard')->with('error', 'Run already ended.');
        }

        if ($request->hasFile('end_meter_photo')) {
            if ($run->meter_photo_path) {
                Storage::disk('public')->delete($run->meter_photo_path);
            }
            $run->meter_photo_path = $request->file('end_meter_photo')->store('vehicle-runs/end', 'public');
        }
        $run->end_route = $request->destination;
        $run->end_km = $request->end_km ?: $run->end_km;
        $run->google_map_link = $request->google_map_link ?: $run->google_map_link;
        $run->distance_km = $request->distance_km ?: $run->distance_km;
        $run->notes = $request->notes ?: $run->notes;
        $run->status = 'ended';
        $run->save();

        return redirect()->route('driver.dashboard')->with('success', 'Day ended. Job saved in My Jobs.');
    }

    /** My Jobs list */
    public function myJobs()
    {
        $driver = auth()->user()->driver;
        if (!$driver) {
            abort(403);
        }
        $runs = VehicleDailyRun::with(['vehicle', 'items'])
            ->where('driver_id', $driver->id)
            ->orderByDesc('run_date')
            ->paginate(15);

        return view('driver.my-jobs', compact('runs'));
    }

    /** Job detail: summary + edit slip/cost */
    public function showJob(VehicleDailyRun $run)
    {
        $driver = auth()->user()->driver;
        if (!$driver || $run->driver_id !== $driver->id) {
            abort(403);
        }
        $run->load('items', 'vehicle');
        return view('driver.job-show', compact('run'));
    }

    /** Update job: edit run details and items (slip, cost) */
    public function updateJob(Request $request, VehicleDailyRun $run)
    {
        $driver = auth()->user()->driver;
        if (!$driver || $run->driver_id !== $driver->id) {
            abort(403);
        }

        $request->validate([
            'start_route' => 'nullable|string|max:255',
            'end_route' => 'nullable|string|max:255',
            'end_km' => 'nullable|numeric|min:0',
            'distance_km' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:vehicle_daily_run_items,id',
            'items.*.cost' => 'nullable|numeric|min:0',
            'items.*.slip' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $run->start_route = $request->start_route ?: $run->start_route;
        $run->end_route = $request->end_route ?: $run->end_route;
        $run->end_km = $request->end_km ?: $run->end_km;
        $run->distance_km = $request->distance_km ?: $run->distance_km;
        $run->notes = $request->notes ?: $run->notes;
        $run->save();

        if ($request->has('items')) {
            foreach ($request->items as $row) {
                $item = VehicleDailyRunItem::where('id', $row['id'])->where('vehicle_daily_run_id', $run->id)->first();
                if (!$item) {
                    continue;
                }
                if (isset($row['cost'])) {
                    $item->cost = $row['cost'];
                }
                if (isset($row['slip']) && $row['slip'] instanceof \Illuminate\Http\UploadedFile) {
                    if ($item->slip_path) {
                        Storage::disk('public')->delete($item->slip_path);
                    }
                    $item->slip_path = $row['slip']->store('vehicle-runs/items', 'public');
                }
                $item->save();
            }
        }

        return redirect()->route('driver.jobs.show', $run)->with('success', 'Job updated.');
    }

    /** Delete an expense item */
    public function deleteItem(Request $request, VehicleDailyRunItem $item)
    {
        $driver = auth()->user()->driver;
        if (!$driver) {
            abort(403);
        }
        $run = $item->run;
        if ($run->driver_id !== $driver->id || $run->status === 'ended') {
            abort(403);
        }
        if ($item->slip_path) {
            Storage::disk('public')->delete($item->slip_path);
        }
        $item->delete();

        return $this->redirectAfterDriverExpense($request)->with('success', 'Expense removed.');
    }

    protected function redirectAfterDriverExpense(Request $request): \Illuminate\Http\RedirectResponse
    {
        $to = $request->input('redirect_to');
        if (is_string($to) && str_starts_with($to, '/driver')) {
            return redirect()->to($to);
        }

        return redirect()->route('driver.dashboard');
    }
}
