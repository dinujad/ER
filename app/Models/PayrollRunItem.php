<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollRunItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id', 'employee_id', 'reference_no',
        'days_present', 'days_absent', 'leave_days', 'ot_minutes', 'ot_amount',
        'nopay_amount', 'epf_amount', 'etf_amount',
        'work_duration', 'work_duration_unit', 'amount_per_unit',
        'basic_salary', 'gross_salary', 'total_allowances', 'total_deductions', 'net_salary', 'details', 'note',
        'payment_status', 'paid_at', 'payment_reference',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'work_duration' => 'decimal:2',
        'amount_per_unit' => 'decimal:2',
        'leave_days' => 'decimal:2',
        'ot_amount' => 'decimal:2',
        'nopay_amount' => 'decimal:2',
        'epf_amount' => 'decimal:2',
        'etf_amount' => 'decimal:2',
        'details' => 'array',
        'paid_at' => 'datetime',
    ];

    const PAYMENT_DUE = 'due';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_PARTIAL = 'partial';

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
