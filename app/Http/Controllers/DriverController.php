<?php

namespace App\Http\Controllers;

use App\Mail\DriverCredentials;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class DriverController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $drivers = Driver::with([
            'user',
            'vehicle',
            'vehicleAssignments' => fn ($q) => $q->whereDate('assignment_date', $today)->with('vehicle'),
        ])->orderBy('name')->get();

        return view('erp.drivers.index', compact('drivers', 'today'));
    }

    public function create()
    {
        return view('erp.drivers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'license_no' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6|required_with:email',
        ]);

        $driver = Driver::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'license_no' => $request->license_no,
        ]);

        if ($request->filled('email')) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'driver',
            ]);
            $driver->update(['user_id' => $user->id]);

            try {
                Mail::to($user->email)->send(new DriverCredentials($user, $request->password));
            } catch (\Exception $e) {
                // Log but don't fail the request
                \Illuminate\Support\Facades\Log::warning('Driver credentials email failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('drivers.index')->with('success', 'Driver added successfully.' . ($request->filled('email') ? ' Credentials sent to their email.' : ''));
    }

    public function edit(Driver $driver)
    {
        return view('erp.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'license_no' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:users,email,' . ($driver->user_id ?? 'null'),
            'password' => 'nullable|string|min:6',
        ]);

        $driver->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'license_no' => $request->license_no,
        ]);

        if ($request->filled('email')) {
            if ($driver->user_id) {
                $user = User::find($driver->user_id);
                $user->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    ...($request->filled('password') ? ['password' => Hash::make($request->password)] : []),
                ]);
                if ($request->filled('password')) {
                    try {
                        Mail::to($user->email)->send(new DriverCredentials($user, $request->password));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('Driver credentials email failed: ' . $e->getMessage());
                    }
                }
            } else {
                $plainPassword = $request->password ?? \Illuminate\Support\Str::random(10);
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($plainPassword),
                    'role' => 'driver',
                ]);
                $driver->update(['user_id' => $user->id]);
                try {
                    Mail::to($user->email)->send(new DriverCredentials($user, $plainPassword));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Driver credentials email failed: ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver)
    {
        if ($driver->user_id) {
            User::where('id', $driver->user_id)->delete();
        }
        $driver->delete();
        return redirect()->route('drivers.index')->with('success', 'Driver deleted.');
    }
}
