<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRawLog;
use App\Models\AttendanceDaily;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AttendanceImportController extends Controller
{
    public function showImportForm()
    {
        return view('erp.attendance.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx|max:2048',
        ]);

        $file = $request->file('file');
        
        // Simple CSV handling for MVP (No Maatwebsite dependency)
        // If user uploads xlsx, this might fail without library. 
        // We generally recommend converting XLS to CSV for raw imports if no lib installed.
        
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        if (count($data) < 1) {
             return back()->with('error', 'File is empty');
        }

        // Assume Header Row exists: EmployeeNo, Date, Time, In/Out
        $header = array_shift($data); 
        
        // Logic to map columns dynamically or assume fixed index?
        // Let's assume fixed for MVP: 
        // 0: EmployeeNo, 1: Date (YYYY-MM-DD), 2: Time (HH:mm:ss), 3: Direction (CheckIn/CheckOut) 
        
        $successCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($data as $index => $row) {
                if (count($row) < 3) continue; // Skip invalid rows

                $empNo = trim((string) ($row[0] ?? ''));
                $dateStr = trim($row[1]);
                $timeStr = trim($row[2]);
                // $direction = trim($row[3] ?? '');

                // Combine Date + Time
                try {
                    $eventTime = Carbon::parse("$dateStr $timeStr");
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": Invalid Date/Time format";
                    continue;
                }

                $employee = Employee::findByHikvisionPersonId($empNo);
                if (! $employee) {
                    $errors[] = "Row " . ($index + 2) . ": Unmatched Hik-Connect person ID " . $empNo;
                    continue;
                }

                // Check for duplicate valid punch
                $uniqueHash = md5($empNo . $eventTime->format('Y-m-d H:i:s'));
                
                if (AttendanceRawLog::where('unique_hash', $uniqueHash)->exists()) {
                    continue; // Skip duplicates
                }

                // Create Raw Log
                $rawLog = AttendanceRawLog::create([
                    'id' => Str::uuid(),
                    'employee_device_id' => $empNo,
                    'event_time' => $eventTime,
                    'event_type' => 'import',
                    'unique_hash' => $uniqueHash,
                    'processed_at' => now(),
                ]);

                // Process Daily Attendance Update
                $this->processDailyAttendance($employee, $eventTime);
                
                $successCount++;
            }
            DB::commit();
            $msg = "Imported $successCount records successfully.";
            if ($errors !== []) {
                $msg .= ' Unmatched/invalid: ' . implode('; ', array_slice($errors, 0, 8));
                if (count($errors) > 8) {
                    $msg .= ' …';
                }
            }
            return redirect()->route('attendance.index')->with($successCount > 0 || $errors === [] ? 'success' : 'error', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    private function processDailyAttendance(Employee $employee, $eventTime)
    {
        // Logic for Logical Day (Night shifts handled simply for now)
        $logicalDate = $eventTime->toDateString();
        
        // Find or Create Daily Record
        $daily = AttendanceDaily::where('employee_id', $employee->id)
            ->whereDate('date', $logicalDate)
            ->firstOrCreate([], [
                'employee_id' => $employee->id,
                'date' => $logicalDate,
                'status' => 'present',
            ]);

        // Update In/Out
        if (!$daily->first_in || $eventTime->lt($daily->first_in)) {
            $daily->first_in = $eventTime;
        }
        
        if (!$daily->last_out || $eventTime->gt($daily->last_out)) {
            $daily->last_out = $eventTime;
        }

        // Recalculate Logic (Simple version)
        if ($daily->first_in && $daily->last_out) {
            $daily->worked_minutes = (int) $daily->first_in->diffInMinutes($daily->last_out);
        }

        // Mark Late (Simple 8:00 AM check for MVP)
        // ideally get shift from $employee->shifts()
        $startThreshold = Carbon::parse($logicalDate . ' 08:30:00'); 
        $daily->late_minutes = 0;
        $daily->status = 'present';
        if ($daily->first_in && $daily->first_in->gt($startThreshold)) {
            $daily->late_minutes = (int) $startThreshold->diffInMinutes($daily->first_in);
             $daily->status = 'late';
        }

        $daily->save();
    }
}
