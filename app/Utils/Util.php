<?php

namespace App\Utils;

class Util
{
    public function num_f($number, $remove_thousands_separator = false, $business = null, $is_quantity = false)
    {
        return number_format((float) $number, 2);
    }

    public function __call($name, $arguments)
    {
        return null;
    }
}
