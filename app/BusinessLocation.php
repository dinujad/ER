<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Stub base class — satisfies Modules\Accounting\Entities\BusinessLocation extends App\BusinessLocation.
 */
class BusinessLocation extends Model
{
    protected $table = 'business_locations';

    protected $guarded = ['id'];

    public static function forDropdown($business_id = null, mixed $append_all = false): array
    {
        if (! Schema::hasTable('business_locations')) {
            return [1 => 'Main'];
        }

        $q = static::query()->orderBy('name');
        if ($business_id !== null) {
            $q->where('business_id', $business_id);
        }

        return $q->pluck('name', 'id')->all();
    }

    public function scopeForBusiness($query, $business_id = null)
    {
        return $query;
    }
}
