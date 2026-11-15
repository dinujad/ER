<?php

namespace Modules\Loan\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Stub — Ultimate POS loan module is not bundled; satisfies Accounting Contact/User relations.
 */
class Loan extends Model
{
    protected $table = 'loans';

    protected $guarded = [];

    public $timestamps = false;
}
