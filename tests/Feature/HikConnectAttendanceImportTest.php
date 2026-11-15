<?php

namespace Tests\Feature;

use App\Models\AttendanceDaily;
use App\Models\AttendanceImport;
use App\Models\Employee;
use App\Services\HikConnectAttendanceImportParser;
use App\Services\HikConnectAttendanceImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HikConnectAttendanceImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
    }

    public function test_duration_parser_treats_clock_style_text_as_minutes(): void
    {
        $parser = app(HikConnectAttendanceImportParser::class);
        $this->assertSame(550, $parser->parseDuration('09:10')['minutes']);
        $this->assertSame(3, $parser->parseDuration('00:03')['minutes']);
        $this->assertSame(0, $parser->parseDuration('00:00')['minutes']);
        $this->assertNull($parser->parseDuration('--')['minutes']);
        $this->assertNull($parser->parseDuration('  ')['minutes']);
        $this->assertSame(1530, $parser->parseDuration('25:30')['minutes']);
    }

    public function test_import_matches_hikvision_id_and_reimport_does_not_duplicate(): void
    {
        $this->employee('0007', 'Nimal');
        $before = Employee::count();
        $path = $this->makeCard([
            $this->row([
                'ID' => '0007', 'First Name' => 'Nimal', 'Department' => 'Sewing', 'Date' => '2026-09-29',
                'Weekday' => 'Tue', 'Timetable' => 'Day',
                'Work Start Date' => '2026-09-29', 'Work Start Time' => '08:00',
                'Work End Date' => '2026-09-29', 'Work End Time' => '17:00',
                'Clock-In Date' => '2026-09-29', 'Clock-In Time' => '08:12', 'Clock-In Source' => 'Device',
                'Clock-Out Date' => '2026-09-29', 'Clock-Out Time' => '17:22', 'Clock-Out Source' => 'Device',
                'Attendance Status' => 'Late', 'Worked Hours' => '09:10', 'Absent Duration' => '00:00',
                'Late Duration' => '00:03', 'Early Leave Duration' => '--',
            ]),
        ]);

        $service = app(HikConnectAttendanceImportService::class);
        $preview = $service->preview($path);
        $this->assertSame(4, $preview['header_row']);
        $this->assertSame('0007', $preview['rows'][0]['hikvision_person_id']);
        $this->assertSame(550, $preview['rows'][0]['worked_minutes']);
        $this->assertSame(3, $preview['rows'][0]['late_minutes']);
        $this->assertNull($preview['rows'][0]['early_leave_minutes']);
        $this->assertSame(HikConnectAttendanceImportService::STATUS_NEW, $preview['rows'][0]['status']);

        $first = $service->confirm($preview['rows'], null, 'Total Time Card.xlsx');
        $this->assertSame(1, $first['created']);
        $this->assertSame($before, Employee::count());
        $this->assertSame(1, AttendanceDaily::count());
        $this->assertSame(1, AttendanceImport::count());

        $daily = AttendanceDaily::first();
        $this->assertSame(Employee::first()->id, $daily->employee_id);
        $this->assertNotSame('0007', (string) $daily->employee_id);
        $this->assertSame('2026-09-29 08:12:00', $daily->first_in->format('Y-m-d H:i:s'));
        $this->assertSame('Late', $daily->hikvision_status);
        $this->assertSame('late', $daily->status);
        $this->assertSame(550, $daily->worked_minutes);
        $this->assertSame('hik-connect', $daily->source);

        $second = $service->confirm($service->preview($path)['rows'], null, 'Total Time Card.xlsx');
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['unchanged']);
        $this->assertSame(1, AttendanceDaily::count());
        $this->assertSame($before, Employee::count());
    }

    public function test_missing_checkout_stays_missing_and_a_later_file_can_fill_it(): void
    {
        $this->employee('0008', 'Amali');
        $service = app(HikConnectAttendanceImportService::class);
        $open = $this->makeCard([
            $this->row([
                'ID' => '0008', 'First Name' => 'Amali', 'Last Name' => 'Perera', 'Department' => 'Packing',
                'Date' => '2026-09-28', 'Timetable' => 'Night',
                'Work Start Date' => '2026-09-28', 'Work Start Time' => '22:00',
                'Work End Date' => '2026-09-29', 'Work End Time' => '06:00',
                'Clock-In Date' => '2026-09-28', 'Clock-In Time' => '22:05', 'Clock-In Source' => 'Device',
                'Attendance Status' => 'Late and Early Leave', 'Worked Hours' => '--', 'Late Duration' => '00:05',
            ]),
        ]);
        $service->confirm($service->preview($open)['rows'], null, 'open.xlsx');
        $daily = AttendanceDaily::first();
        $this->assertSame('2026-09-28 22:05:00', $daily->first_in->format('Y-m-d H:i:s'));
        $this->assertNull($daily->last_out);
        $this->assertSame('incomplete', $daily->status);
        $this->assertNull($daily->worked_minutes);
        $this->assertSame('2026-09-28', $daily->date->format('Y-m-d'));

        $closed = $this->makeCard([
            $this->row([
                'ID' => '0008', 'First Name' => 'Amali', 'Last Name' => 'Perera', 'Department' => 'Packing',
                'Date' => '2026-09-28', 'Timetable' => 'Night',
                'Work Start Date' => '2026-09-28', 'Work Start Time' => '22:00',
                'Work End Date' => '2026-09-29', 'Work End Time' => '06:00',
                'Clock-In Date' => '2026-09-28', 'Clock-In Time' => '22:05', 'Clock-In Source' => 'Device',
                'Clock-Out Date' => '2026-09-29', 'Clock-Out Time' => '06:10', 'Clock-Out Source' => 'Device',
                'Attendance Status' => 'Late and Early Leave', 'Worked Hours' => '08:05',
                'Late Duration' => '00:05', 'Early Leave Duration' => '00:10',
            ]),
        ]);
        $preview = $service->preview($closed);
        $this->assertSame(HikConnectAttendanceImportService::STATUS_UPDATE, $preview['rows'][0]['status']);
        $service->confirm($preview['rows'], null, 'closed.xlsx');

        $daily = AttendanceDaily::first();
        $this->assertSame(1, AttendanceDaily::count());
        $this->assertSame('2026-09-29 06:10:00', $daily->last_out->format('Y-m-d H:i:s'));
        $this->assertSame('late', $daily->status);
        $this->assertSame(10, $daily->early_leave_minutes);
    }

    public function test_unknown_id_is_reported_and_manual_records_are_protected(): void
    {
        $employee = $this->employee('0010', 'Kamal');
        AttendanceDaily::create([
            'employee_id' => $employee->id,
            'date' => '2026-09-01',
            'session_key' => '',
            'status' => 'present',
            'source' => 'manual',
            'manually_corrected' => true,
            'is_finalized' => false,
            'first_in' => '2026-09-01 09:00:00',
            'last_out' => '2026-09-01 17:00:00',
            'worked_minutes' => 480,
        ]);

        $path = $this->makeCard([
            $this->row([
                'ID' => '9999', 'First Name' => 'Nobody', 'Department' => 'Sewing', 'Date' => '2026-09-02',
                'Timetable' => 'Day', 'Work Start Date' => '2026-09-02', 'Work Start Time' => '08:00',
                'Work End Date' => '2026-09-02', 'Work End Time' => '17:00',
                'Clock-In Date' => '2026-09-02', 'Clock-In Time' => '08:00', 'Clock-In Source' => 'Device',
                'Clock-Out Date' => '2026-09-02', 'Clock-Out Time' => '17:00', 'Clock-Out Source' => 'Device',
                'Attendance Status' => 'Normal', 'Worked Hours' => '09:00',
            ]),
            $this->row([
                'ID' => '0010', 'First Name' => 'Kamal', 'Department' => 'Sewing', 'Date' => '2026-09-01',
                'Timetable' => 'Day', 'Work Start Date' => '2026-09-01', 'Work Start Time' => '08:00',
                'Work End Date' => '2026-09-01', 'Work End Time' => '17:00',
                'Clock-In Date' => '2026-09-01', 'Clock-In Time' => '08:30', 'Clock-In Source' => 'Device',
                'Clock-Out Date' => '2026-09-01', 'Clock-Out Time' => '17:30', 'Clock-Out Source' => 'Device',
                'Attendance Status' => 'Late', 'Worked Hours' => '09:00', 'Late Duration' => '00:30',
            ]),
        ]);
        $service = app(HikConnectAttendanceImportService::class);
        $preview = $service->preview($path);
        $this->assertSame(HikConnectAttendanceImportService::STATUS_UNMATCHED, $preview['rows'][0]['status']);
        $this->assertStringContainsString('9999', $preview['rows'][0]['message']);
        $this->assertSame(HikConnectAttendanceImportService::STATUS_CONFLICT, $preview['rows'][1]['status']);

        $result = $service->confirm($preview['rows'], null, 'card.xlsx');
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['failed']);
        $this->assertSame(1, Employee::count());
        $this->assertSame(1, AttendanceDaily::count());
        $this->assertSame('2026-09-01 09:00:00', AttendanceDaily::first()->first_in->format('Y-m-d H:i:s'));
    }

    public function test_no_punches_are_not_marked_present_and_two_timetables_are_kept(): void
    {
        $this->employee('0011', 'Saman');
        $service = app(HikConnectAttendanceImportService::class);
        $path = $this->makeCard([
            $this->row([
                'ID' => '0011', 'First Name' => 'Saman', 'Department' => 'Cutting', 'Date' => '2026-09-03',
                'Timetable' => 'Day', 'Work Start Date' => '2026-09-03', 'Work Start Time' => '08:00',
                'Work End Date' => '2026-09-03', 'Work End Time' => '17:00',
                'Attendance Status' => 'Late and Early Leave', 'Worked Hours' => '--',
            ]),
            $this->row([
                'ID' => '0011', 'First Name' => 'Saman', 'Department' => 'Cutting', 'Date' => '2026-09-04',
                'Timetable' => 'Morning', 'Work Start Date' => '2026-09-04', 'Work Start Time' => '08:00',
                'Work End Date' => '2026-09-04', 'Work End Time' => '12:00',
                'Clock-In Date' => '2026-09-04', 'Clock-In Time' => '08:00', 'Clock-In Source' => 'Device',
                'Clock-Out Date' => '2026-09-04', 'Clock-Out Time' => '12:00', 'Clock-Out Source' => 'Device',
                'Attendance Status' => 'Normal', 'Worked Hours' => '04:00',
            ]),
            $this->row([
                'ID' => '0011', 'First Name' => 'Saman', 'Department' => 'Cutting', 'Date' => '2026-09-04',
                'Timetable' => 'Evening', 'Work Start Date' => '2026-09-04', 'Work Start Time' => '13:00',
                'Work End Date' => '2026-09-04', 'Work End Time' => '17:00',
                'Clock-In Date' => '2026-09-04', 'Clock-In Time' => '13:00', 'Clock-In Source' => 'Device',
                'Clock-Out Date' => '2026-09-04', 'Clock-Out Time' => '17:00', 'Clock-Out Source' => 'Device',
                'Attendance Status' => 'Normal', 'Worked Hours' => '04:00',
            ]),
        ]);
        $preview = $service->preview($path);
        $this->assertSame('absent', $preview['rows'][0]['hrm_status']);
        $this->assertSame(HikConnectAttendanceImportService::STATUS_NEW, $preview['rows'][1]['status']);
        $this->assertSame(HikConnectAttendanceImportService::STATUS_NEW, $preview['rows'][2]['status']);
        $this->assertNotSame($preview['rows'][1]['session_key'], $preview['rows'][2]['session_key']);

        $service->confirm($preview['rows'], null, 'card.xlsx');
        $this->assertSame(3, AttendanceDaily::count());
        $this->assertSame('absent', AttendanceDaily::whereDate('date', '2026-09-03')->first()->status);
        $this->assertSame(2, AttendanceDaily::whereDate('date', '2026-09-04')->count());
    }

    protected function employee(string $hikId, string $name): Employee
    {
        return Employee::create([
            'name' => $name,
            'first_name' => $name,
            'nic' => 'IMP-' . $hikId,
            'contact' => '',
            'role' => 'employee',
            'join_date' => '2024-01-01',
            'status' => 'active',
            'hikvision_person_id' => $hikId,
            'hikvision_source' => Employee::HIK_SOURCE_HIK_CONNECT,
        ]);
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    protected function row(array $values): array
    {
        return $values;
    }

    /**
     * @param  list<array<string, string>>  $records
     */
    protected function makeCard(array $records): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet0');
        $sheet->setCellValue('A1', 'Dermas Apparel');
        $sheet->setCellValue('A2', 'Total Time Card');
        $sheet->setCellValue('A3', 'Export time: 2026-09-30 02:00:00');

        $headers = [
            'First Name', 'Last Name', 'ID', 'Department', 'Date', 'Weekday', 'Timetable',
            'Work Start Date', 'Work Start Time', 'Work End Date', 'Work End Time',
            'Clock-In Date', 'Clock-In Time', 'Clock-In Source',
            'Clock-Out Date', 'Clock-Out Time', 'Clock-Out Source',
            'Attendance Status', 'Worked Hours', 'Absent Duration', 'Late Duration',
            'Early Leave Duration', 'Break Duration', 'Leave Duration', 'Overtime Duration',
            'Workday Overtime Duration', 'Weekend Overtime Duration',
        ];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 4, $header);
        }
        foreach ($records as $offset => $record) {
            $excelRow = 5 + $offset;
            foreach ($headers as $i => $header) {
                $value = $record[$header] ?? '';
                if ($header === 'ID') {
                    $sheet->setCellValueExplicit('C' . $excelRow, (string) $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValueByColumnAndRow($i + 1, $excelRow, $value);
                }
            }
        }

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hik-card-' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    protected function createTables(): void
    {
        Schema::dropIfExists('attendance_imports');
        Schema::dropIfExists('attendance_manual_adjustments');
        Schema::dropIfExists('attendance_dailies');
        Schema::dropIfExists('employees');

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('nic')->unique();
            $table->string('contact')->nullable();
            $table->string('role')->default('employee');
            $table->date('join_date')->nullable();
            $table->string('hikvision_person_id', 64)->nullable();
            $table->string('hikvision_source', 50)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('attendance_dailies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('date');
            $table->string('session_key', 191)->default('');
            $table->dateTime('first_in')->nullable();
            $table->dateTime('last_out')->nullable();
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->integer('worked_minutes')->nullable();
            $table->integer('late_minutes')->nullable();
            $table->integer('overtime_minutes')->nullable();
            $table->unsignedInteger('absent_minutes')->nullable();
            $table->unsignedInteger('early_leave_minutes')->nullable();
            $table->unsignedInteger('break_minutes')->nullable();
            $table->unsignedInteger('leave_minutes')->nullable();
            $table->unsignedInteger('workday_overtime_minutes')->nullable();
            $table->unsignedInteger('weekend_overtime_minutes')->nullable();
            $table->string('status')->default('absent');
            $table->string('source', 40)->nullable();
            $table->string('hikvision_status')->nullable();
            $table->string('timetable')->nullable();
            $table->string('clock_in_source')->nullable();
            $table->string('clock_out_source')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->boolean('manually_corrected')->default(false);
            $table->timestamps();
            $table->unique(['employee_id', 'date', 'session_key']);
        });

        Schema::create('attendance_manual_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_daily_id');
            $table->timestamps();
        });

        Schema::create('attendance_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('filename');
            $table->string('source', 40)->default('hik-connect');
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();
        });
    }
}
