<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Stub base class — satisfies Modules\Accounting\Entities\Currency extends App\Currency.
 */
class Currency extends Model
{
    protected $table = 'currencies';

    protected $guarded = ['id'];
}
