<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'name',
        'first_name',
        'last_name',
        'email',
        'nic',
        'birthday',
        'contact',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'id_type',
        'employment_type',
        'pay_type',
        'pay_rate',
        'bank_name',
        'bank_branch',
        'bank_account_name',
        'bank_account_number',
        'payroll_registration_no',
        'reporting_manager_id',
        'role',
        'assigned_line',
        'join_date',
        'user_id',
        'production_line_id',
        'effective_period_start',
        'effective_period_end',
        'self_service',
        'photo',
        // HRM Fields
        'department_id',
        'designation_id',
        'shift_id',
        'device_enroll_id',
        'hikvision_person_id',
        'hikvision_source',
        'hikvision_valid_from',
        'hikvision_valid_to',
        'gender',
        'notes',
        'status', // active, resigned, etc.
    ];

    public const HIK_SOURCE_HIK_CONNECT = 'hik-connect';

    protected $casts = [
        'join_date' => 'date',
        'birthday' => 'date',
        'effective_period_start' => 'date',
        'effective_period_end' => 'date',
        'hikvision_valid_from' => 'datetime',
        'hikvision_valid_to' => 'datetime',
        'self_service' => 'boolean',
        'pay_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productionSection()
    {
        return $this->belongsTo(ProductionSection::class, 'production_section_id');
    }

    // HRM Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function defaultShift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function attendanceDailies()
    {
        return $this->hasMany(AttendanceDaily::class);
    }

    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'employee_shifts')
                    ->withPivot(['date', 'is_off_day'])
                    ->withTimestamps();
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function employeeSalaries()
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function payrollRunItems()
    {
        return $this->hasMany(PayrollRunItem::class);
    }

    public function currentSalary(): ?EmployeeSalary
    {
        return EmployeeSalary::currentForEmployee($this->id);
    }

    public function bonuses()
    {
        return $this->hasMany(EmployeeBonus::class);
    }

    public function reportingManager()
    {
        return $this->belongsTo(self::class, 'reporting_manager_id');
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function changes()
    {
        return $this->hasMany(EmployeeChange::class)->latest('effective_date');
    }

    public static function codeForId(int $id): string
    {
        return 'EMP-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        }
        return $this->name ?? '';
    }

    public static function normalizeHikvisionPersonId(mixed $id): ?string
    {
        if ($id === null) {
            return null;
        }
        $id = trim((string) $id);
        if ($id === '') {
            return null;
        }

        return $id;
    }

    public static function findByHikvisionPersonId(mixed $personId, string $source = self::HIK_SOURCE_HIK_CONNECT): ?self
    {
        $personId = static::normalizeHikvisionPersonId($personId);
        if ($personId === null) {
            return null;
        }

        $match = static::query()
            ->where('hikvision_source', $source)
            ->where('hikvision_person_id', $personId)
            ->first();

        if ($match) {
            return $match;
        }

        return static::query()
            ->where('device_enroll_id', $personId)
            ->where(function ($q) {
                $q->whereNull('hikvision_person_id')->orWhere('hikvision_person_id', '');
            })
            ->first();
    }

    public function scopeUnmappedHikvision($query, string $source = self::HIK_SOURCE_HIK_CONNECT)
    {
        return $query->where(function ($q) use ($source) {
            $q->whereNull('hikvision_person_id')
                ->orWhere('hikvision_person_id', '')
                ->orWhere(function ($inner) use ($source) {
                    $inner->whereNotNull('hikvision_person_id')
                        ->where('hikvision_source', '!=', $source);
                });
        });
    }
}

