<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'label', 'description', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /** True if this role has a specific permission key. */
    public function hasPermission(string $key): bool
    {
        // Admin always has everything
        if ($this->name === 'admin') {
            return true;
        }
        $perms = $this->permissions ?? [];
        return in_array($key, $perms, true);
    }

    /** True if this role is the built-in admin (cannot be deleted). */
    public function isAdmin(): bool
    {
        return $this->name === 'admin';
    }

    /** All permission keys that belong to all groups, flat list. */
    public static function allPermissionKeys(): array
    {
        $keys = [];
        foreach (config('permissions') as $group) {
            foreach (array_keys($group['permissions']) as $k) {
                $keys[] = $k;
            }
        }
        return $keys;
    }
}
