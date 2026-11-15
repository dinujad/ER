<?php

namespace Modules\Accounting\Entities;

use App\User as AppUser;
use Illuminate\Support\Facades\Schema;
use Modules\EmployeeLoan\Entities\EmployeeLoan;
use Modules\Loan\Entities\Loan;

class User extends AppUser
{
    protected $appends = ['user_full_name'];

    public function loans()
    {
        return $this->hasMany(Loan::class, 'contact_id', 'id');
    }
    
    public function employeeloans()
    {
        return $this->hasMany(EmployeeLoan::class, 'user_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('users.status', 'active');
    }

    public function scopeLoanOfficer($query)
    {
        return $query->where('users.user_type', '!=', 'user_customer');
    }

    public function scopeEmployeeLoanOfficer($query)
    {
        return $query->where('users.user_type', '!=', 'user_customer');
    }

    public function getIsAdminAttribute()
    {
        $roles = $this->getRoleNames();

        return $roles->contains(function ($value) {
            return str_contains($value, 'Admin');
        });
    }

    public function getIsEmployeeAttribute()
    {
        $roles = $this->getRoleNames();

        return $roles->contains(function ($value) {
            return str_contains($value, 'Employee');
        });
    }

    public function scopeForBusiness($query)
    {
        if (Schema::hasColumn($query->getModel()->getTable(), 'business_id')) {
            return $query->where('users.business_id', session('business.id'));
        }

        return $query;
    }

    /**
     * Users for filter dropdowns (SellUtil, ExpenseUtil, etc.).
     *
     * @param  mixed  $business_id
     * @param  bool  $prepend_none  When true, prepend an empty "All" option.
     * @param  bool  $only_commission_agents  Filter to commission agents when schema supports it.
     * @param  bool  $show_role  Append role to the display label.
     */
    public static function forDropdown($business_id = null, $prepend_none = true, $only_commission_agents = false, $show_role = false): array
    {
        try {
            $table = (new static)->getTable();
            $q = static::query()->orderBy('name');

            if ($business_id !== null && Schema::hasColumn($table, 'business_id')) {
                $q->where('business_id', $business_id);
            }

            if ($only_commission_agents) {
                if (Schema::hasColumn($table, 'user_type')) {
                    $q->where('user_type', 'commission_agent');
                } elseif (Schema::hasColumn($table, 'is_commission_agent')) {
                    $q->where('is_commission_agent', 1);
                } else {
                    $q->whereRaw('1 = 0');
                }
            }

            $rows = $q->get(['id', 'name', 'email', 'role'])->keyBy('id');
            $out = [];
            foreach ($rows as $user) {
                $label = $user->name ?: ($user->email ?: ('User #'.$user->id));
                if ($show_role && ! empty($user->role)) {
                    $label .= ' ('.$user->role.')';
                }
                $out[$user->id] = $label;
            }

            if ($prepend_none) {
                $out = ['' => __('lang_v1.all')] + $out;
            }
        } catch (\Throwable $e) {
            $out = $prepend_none ? ['' => 'All'] : [];
        }

        return $out;
    }

    public static function forLoanOfficerDropdown($business_id)
    {
        return User::forBusiness()->loanOfficer()->where('business_id', $business_id)->get()->pluck('user_full_name', 'id');
    }
}
