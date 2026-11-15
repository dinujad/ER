<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceImportController;
use App\Models\AttendanceDaily;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceCalculationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('hikvision_person_id');
            $table->string('hikvision_source')->default('hik-connect');
            $table->string('device_enroll_id')->nullable();
        });
        Schema::create('attendance_raw_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employee_device_id');
            $table->dateTime('event_time');
            $table->string('event_type');
            $table->string('unique_hash')->unique();
            $table->dateTime('processed_at');
            $table->timestamps();
        });
        Schema::create('attendance_dailies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->string('session_key')->default('');
            $table->dateTime('first_in')->nullable();
            $table->dateTime('last_out')->nullable();
            $table->integer('worked_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->string('status');
            $table->string('source')->nullable();
            $table->boolean('manually_corrected')->default(false);
            $table->timestamps();
        });

        DB::table('employees')->insert(['id' => 1, 'hikvision_person_id' => '123']);
    }

    public function test_csv_import_records_positive_worked_and_late_minutes(): void
    {
        $this->importPunches(['17:00:30', '09:00:00']);

        $daily = AttendanceDaily::sole();
        $this->assertSame(480, $daily->worked_minutes);
        $this->assertSame(30, $daily->late_minutes);
        $this->assertSame('late', $daily->status);
    }

    public function test_earlier_imported_punch_clears_previous_lateness(): void
    {
        $this->importPunches(['09:00:00', '17:00:00']);
        $this->importPunches(['08:00:00']);

        $daily = AttendanceDaily::sole();
        $this->assertSame(540, $daily->worked_minutes);
        $this->assertSame(0, $daily->late_minutes);
        $this->assertSame('present', $daily->status);
    }

    public function test_manual_attendance_records_positive_worked_minutes(): void
    {
        $request = Request::create('/', 'POST', [
            'date' => '2026-09-01',
            'rows' => [[
                'employee_id' => 1,
                'check_in' => '08:00:00',
                'check_out' => '17:00:30',
                'status' => 'present',
            ]],
        ]);

        (new AttendanceController())->storeManual($request);

        $daily = AttendanceDaily::sole();
        $this->assertSame(540, $daily->worked_minutes);
        $this->assertSame('present', $daily->status);
    }

    private function importPunches(array $times): void
    {
        $csv = "EmployeeNo,Date,Time\n";
        foreach ($times as $time) {
            $csv .= "123,2026-09-01,{$time}\n";
        }

        $path = tempnam(storage_path('framework'), 'attendance-test-');
        file_put_contents($path, $csv);

        try {
            $request = Request::create('/', 'POST', [], [], [
                'file' => new UploadedFile($path, 'attendance.csv', 'text/csv', null, true),
            ]);

            $response = (new AttendanceImportController())->import($request);
            $this->assertSame(route('attendance.index'), $response->getTargetUrl());
        } finally {
            unlink($path);
        }
    }
}
