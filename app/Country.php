<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Stub base class — satisfies use App\Country in accounting entities.
 */
class Country extends Model
{
    protected $table = 'countries';

    protected $guarded = ['id'];
}
