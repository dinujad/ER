<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Services\HikConnectEmployeeImportParser;
use App\Services\HikConnectEmployeeImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class HikConnectEmployeeImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createEmployeeTables();
    }

    public function test_parser_finds_header_row_skips_instructions_and_preserves_id_strings(): void
    {
        $path = $this->makeWorkbook([
            ['0007', 'Nimal', '', 'Cutting/Line 1', '', '0711111111', 'Night shift', 'Unknown', '2020/01/01 00:00:00', '2035/12/31 23:59:59', 'FINGERPRINT-SECRET', 'PIN-SECRET'],
            ['', '', '', '', '', '', '', '', '', '', '', ''],
            ['0008', 'Amali', 'Perera', 'Cutting/Line 1', 'amali@example.com', '', '', 'Female', '', '', '', ''],
        ]);

        $parsed = app(HikConnectEmployeeImportParser::class)->parse($path);

        $this->assertSame(15, $parsed['header_row']);
        $this->assertCount(2, $parsed['rows']);
        $this->assertSame('0007', $parsed['rows'][0]['hikvision_person_id']);
        $this->assertSame('Nimal', $parsed['rows'][0]['display_name']);
        $this->assertNull($parsed['rows'][0]['last_name']);
        $this->assertNull($parsed['rows'][0]['gender']);
        $this->assertSame('Cutting/Line 1', $parsed['rows'][0]['department']);
        $this->assertArrayNotHasKey('fingerprint', $parsed['rows'][0]);
        $this->assertArrayNotHasKey('pin', $parsed['rows'][0]);
        $this->assertSame('0008', $parsed['rows'][1]['hikvision_person_id']);
        $this->assertSame('female', $parsed['rows'][1]['gender']);
    }

    public function test_reimporting_the_same_file_does_not_create_duplicates_and_id_resolves(): void
    {
        $path = $this->makeWorkbook([
            ['0007', 'Nimal', '', 'Sewing', 'nimal@example.com', '0710000000', 'note one', 'Male', '2019/05/01 08:00:00', '2030/01/01 00:00:00', 'SECRET', '9999'],
        ]);

        $service = app(HikConnectEmployeeImportService::class);
        $preview = $service->preview($path);
        $first = $service->confirm($preview['rows']);
        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $first['failed']);
        $this->assertSame(1, Employee::count());

        $secondPreview = $service->preview($path);
        $this->assertSame(HikConnectEmployeeImportService::STATUS_EXISTING, $secondPreview['rows'][0]['status']);
        $second = $service->confirm($secondPreview['rows']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['updated']);
        $this->assertSame(1, Employee::count());

        $employee = Employee::findByHikvisionPersonId('0007');
        $this->assertNotNull($employee);
        $this->assertSame('0007', $employee->hikvision_person_id);
        $this->assertSame(Employee::HIK_SOURCE_HIK_CONNECT, $employee->hikvision_source);
        $this->assertNotEquals('0007', (string) $employee->id);
        $this->assertStringStartsWith('IMP-', $employee->nic);
        $this->assertNotEquals('2019-05-01', optional($employee->join_date)?->format('Y-m-d'));
        $this->assertSame('2019-05-01 08:00:00', optional($employee->hikvision_valid_from)?->format('Y-m-d H:i:s'));
        $this->assertSame('Sewing', $employee->department?->name);
    }

    public function test_blank_cells_do_not_erase_and_conflicts_are_not_overwritten(): void
    {
        $path = $this->makeWorkbook([
            ['0042', 'Kamal', 'Fernando', 'Packing', 'kamal@dermas.lk', '0771234567', 'keep me', 'Male', '', '', '', ''],
        ]);
        $service = app(HikConnectEmployeeImportService::class);
        $service->confirm($service->preview($path)['rows']);

        $blankPath = $this->makeWorkbook([
            ['0042', 'Kamal', '', 'Packing', '', '', '', 'Unknown', '', '', '', ''],
        ]);
        $service->confirm($service->preview($blankPath)['rows']);

        $employee = Employee::findByHikvisionPersonId('0042');
        $this->assertSame('kamal@dermas.lk', $employee->email);
        $this->assertSame('0771234567', $employee->contact);
        $this->assertSame('Fernando', $employee->last_name);
        $this->assertSame('keep me', $employee->notes);
        $this->assertSame('male', $employee->gender);

        $other = Employee::create([
            'name' => 'Other Person',
            'nic' => '900000000V',
            'contact' => '0700000000',
            'role' => 'employee',
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $classified = $service->classify($service->preview($blankPath)['rows'][0], [
            (int) $service->preview($blankPath)['rows'][0]['excel_row'] => $other->id,
        ]);
        $this->assertSame(HikConnectEmployeeImportService::STATUS_CONFLICT, $classified['status']);
        $this->assertSame($employee->id, Employee::findByHikvisionPersonId('0042')->id);
    }

    public function test_existing_employee_is_not_matched_by_name_without_explicit_link(): void
    {
        Employee::create([
            'name' => 'Nimal',
            'first_name' => 'Nimal',
            'nic' => '911111111V',
            'contact' => '0719999999',
            'role' => 'employee',
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $path = $this->makeWorkbook([
            ['0100', 'Nimal', '', 'Stores', '', '', '', '', '', '', '', ''],
        ]);
        $preview = app(HikConnectEmployeeImportService::class)->preview($path);
        $this->assertSame(HikConnectEmployeeImportService::STATUS_NEW, $preview['rows'][0]['status']);
        $this->assertNull($preview['rows'][0]['employee_id']);
    }

    public function test_explicit_link_maps_hikvision_id_without_creating_a_duplicate(): void
    {
        $existing = Employee::create([
            'name' => 'Existing Staff',
            'nic' => '922222222V',
            'contact' => '0701111111',
            'role' => 'employee',
            'join_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $path = $this->makeWorkbook([
            ['0555', 'Existing', 'Staff', 'Stores', '', '0701111111', '', '', '', '', '', ''],
        ]);
        $service = app(HikConnectEmployeeImportService::class);
        $preview = $service->preview($path);
        $excelRow = (int) $preview['rows'][0]['excel_row'];
        $result = $service->confirm($preview['rows'], [$excelRow => $existing->id]);

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, Employee::count());
        $this->assertSame('0555', $existing->fresh()->hikvision_person_id);
        $this->assertSame($existing->id, Employee::findByHikvisionPersonId('0555')->id);
    }

    /**
     * @param  list<list<string>>  $people
     */
    protected function makeWorkbook(array $people): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');

        for ($r = 1; $r <= 14; $r++) {
            $sheet->setCellValue('A' . $r, 'Instruction row ' . $r . ' — do not import');
        }

        $headers = [
            'ID', '*First Name', '*Last Name', '*Department', 'Email', 'Phone', 'Remarks', 'Gender',
            'Start Time of Effective Period', 'End Time of Effective Period',
            'Fingerprint', 'PIN', 'Card', 'Self-Service', 'Extended Access',
            'Open Door via Bluetooth', 'Device Administrator', 'Super Access Permission',
        ];
        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 15, $header);
        }

        foreach ($people as $offset => $person) {
            $row = 16 + $offset;
            $sheet->setCellValueExplicit('A' . $row, (string) $person[0], DataType::TYPE_STRING);
            foreach (array_slice($person, 1) as $i => $value) {
                $sheet->setCellValueByColumnAndRow($i + 2, $row, $value);
            }
        }

        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hik-person-' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    protected function createEmployeeTables(): void
    {
        Schema::dropIfExists('employees');
        Schema::dropIfExists('departments');

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('nic')->unique();
            $table->string('contact')->nullable();
            $table->string('role')->default('employee');
            $table->date('join_date')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('device_enroll_id')->nullable();
            $table->string('hikvision_person_id', 64)->nullable();
            $table->string('hikvision_source', 50)->nullable();
            $table->dateTime('hikvision_valid_from')->nullable();
            $table->dateTime('hikvision_valid_to')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->boolean('self_service')->default(false);
            $table->timestamps();
            $table->unique(['hikvision_source', 'hikvision_person_id'], 'employees_hikvision_source_person_unique');
        });
    }
}
