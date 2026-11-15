<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'product_name',
        'quantity_sold',
        'total_amount',
        'sale_date',
    ];
}
