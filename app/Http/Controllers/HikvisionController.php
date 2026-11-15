<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRawLog;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HikvisionController extends Controller
{
    public function handleEvent(Request $request)
    {
        try {
            // Log incoming request for debug
            Log::info('Hikvision Event Received', ['payload' => $request->all()]);

            $payload = $request->all();
            
            // Validate basic structure (Hikvision usually sends AccessControllerEvent)
            if (!isset($payload['AccessControllerEvent'])) {
                return response()->json(['message' => 'Invalid payload format'], 400);
            }

            $event = $payload['AccessControllerEvent'];
            
            // Extract key fields
            $deviceId = $payload['macAddress'] ?? ($payload['ipAddress'] ?? 'unknown_device');
            $employeeNoString = $event['employeeNoString'] ?? null;
            $eventTime = $event['time'] ?? now();
            $eventType = $payload['eventType'] ?? 'AccessControl';

            if (!$employeeNoString) {
                return response()->json(['message' => 'No employee ID found'], 200); // Ack anyway
            }

            // Create Unique Hash to prevent duplicates
            // Combination of Device + Time + Employee
            $uniqueHash = md5($deviceId . $eventTime . $employeeNoString);

            // Store Raw Log
            AttendanceRawLog::create([
                'id' => Str::uuid(),
                'device_ip' => $payload['ipAddress'] ?? null,
                'device_id' => $deviceId,
                'employee_device_id' => $employeeNoString,
                'event_time' => $eventTime,
                'event_type' => $eventType,
                'payload_json' => $payload,
                'unique_hash' => $uniqueHash,
            ]);

            // Dispatch Job for further processing (Shift logic, Late calculation)
            // ProcessAttendance::dispatch($rawLog); 
            // For MVP, we can do inline processing or leave for a scheduled job

            return response()->json(['message' => 'Event received successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Hikvision Event Error: ' . $e->getMessage());
            // Return 200 even on error to prevent device from retrying endlessly? 
            // Better to return 500 so it retries if important.
            return response()->json(['message' => 'Error processing event'], 500); 
        }
    }
}
