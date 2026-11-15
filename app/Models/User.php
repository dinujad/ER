<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Employee;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',            // legacy string role (kept for backward compat)
        'role_id',         // FK to roles table (new permission-based system)
        'sales_route_id',
        'bank_account_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /** Check a permission key against the user's assigned role. Admin (role_id null or role='admin') bypasses all checks. */
    public function can_access(string $permission): bool
    {
        // Legacy admin string or no role_id → full access
        if ($this->role === 'admin' && !$this->role_id) {
            return true;
        }
        $roleModel = $this->roleModel;
        if (!$roleModel) {
            return $this->role === 'admin';
        }
        return $roleModel->hasPermission($permission);
    }

    /** True for an unrestricted legacy admin or a user assigned the built-in Admin role. */
    public function isSystemAdmin(): bool
    {
        return ($this->role === 'admin' && !$this->role_id)
            || ($this->role_id && $this->roleModel?->isAdmin());
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'added_by');
    }

    public function salesRoute()
    {
        return $this->belongsTo(SalesRoute::class, 'sales_route_id');
    }

    public function salesRouteRuns()
    {
        return $this->hasMany(SalesRouteRun::class, 'user_id');
    }

    public function salesOrders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Sales person IDs that share this user's sales route (includes self).
     * If no route assigned, returns only this user's id.
     *
     * @return array<int, int>
     */
    public function salesRoutePeerIds(): array
    {
        if ($this->role !== 'sales_person') {
            return [(int) $this->id];
        }

        if (!$this->sales_route_id) {
            return [(int) $this->id];
        }

        return static::query()
            ->where('role', 'sales_person')
            ->where('sales_route_id', $this->sales_route_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** Customers visible on portal: own + peers on same sales route. */
    public function visibleCustomerOwnerIds(): array
    {
        return $this->salesRoutePeerIds();
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Spatie-compatible adapter used by the Accounting module. */
    public function hasPermissionTo(string|\BackedEnum $permission, ?string $guardName = null): bool
    {
        $key = $permission instanceof \BackedEnum ? (string) $permission->value : $permission;

        return $this->can_access($key);
    }

    /** @param string|array<int, string> ...$permissions */
    public function hasAnyPermission(...$permissions): bool
    {
        $keys = collect($permissions)->flatten();

        return $keys->contains(fn ($permission) => $this->hasPermissionTo($permission));
    }
}
