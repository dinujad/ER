<?php

namespace App;

use App\Models\User as BaseUser;

/**
 * Stub base class — satisfies Modules\Accounting\Entities\User extends App\User.
 * Delegates to the real App\Models\User so auth/fillable/etc all work.
 */
class User extends BaseUser
{
    protected $table = 'users';

    public function scopeActive($query)
    {
        return $query->where('users.status', 'active');
    }
}
