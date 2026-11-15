<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::orderBy('start_time')->paginate(15);
        return view('erp.hrm.shifts.index', compact('shifts'));
    }

    public function create()
    {
        return view('erp.hrm.shifts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Shift::create($data);
        return redirect()->route('shifts.index')->with('success', 'Shift created.');
    }

    public function edit(Shift $shift)
    {
        return view('erp.hrm.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        $shift->update($this->validated($request));
        return redirect()->route('shifts.index')->with('success', 'Shift updated.');
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'grace_period_mins' => 'nullable|integer|min:0',
            'late_threshold_mins' => 'nullable|integer|min:0',
            'ot_grace_mins' => 'nullable|integer|min:0',
            'ot_rate_per_hour' => 'nullable|numeric|min:0',
            'weekend_ot_rate_per_hour' => 'nullable|numeric|min:0',
            'holiday_ot_rate_per_hour' => 'nullable|numeric|min:0',
            'ot_daily_limit_hours' => 'nullable|numeric|min:0',
            'is_overnight' => 'nullable|boolean',
        ]);

        return [
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'grace_period_mins' => $request->input('grace_period_mins', 15),
            'late_threshold_mins' => $request->input('late_threshold_mins', 30),
            'early_leave_allowed_mins' => $request->input('early_leave_allowed_mins', 0),
            'is_overnight' => $request->boolean('is_overnight'),
            'ot_grace_mins' => (int) $request->input('ot_grace_mins', 0),
            'ot_rate_per_hour' => round((float) $request->input('ot_rate_per_hour', 0), 2),
            'weekend_ot_rate_per_hour' => round((float) $request->input('weekend_ot_rate_per_hour', 0), 2),
            'holiday_ot_rate_per_hour' => round((float) $request->input('holiday_ot_rate_per_hour', 0), 2),
            'ot_daily_limit_hours' => round((float) $request->input('ot_daily_limit_hours', 4), 2),
        ];
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();
        return redirect()->route('shifts.index')->with('success', 'Shift deleted.');
    }
}
