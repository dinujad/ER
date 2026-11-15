<?php

namespace Modules\EmployeeLoan\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Stub — satisfies Accounting Contact/User relations when those relationships are resolved.
 */
class EmployeeLoan extends Model
{
    protected $table = 'employee_loans';

    protected $guarded = [];

    public $timestamps = false;
}
